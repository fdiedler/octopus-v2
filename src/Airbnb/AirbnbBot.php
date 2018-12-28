<?php

namespace App\Airbnb;

use App\Entity\Cron;
use App\Entity\Host;
use App\Entity\Property;
use App\Entity\Reservation;
use App\Mail\MailboxFactory;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Http\ClientFactory;
use App\Reference\ReferenceGenerator;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use Money\Parser\IntlMoneyParser;

/**
 * Bot that synchronizes AirBNB iCal for all properties
 *
 * @author DIEDLER Florent <flornet@wehost.fr>
 */
class AirbnbBot
{
    private $doctrine;
    private $clientFactory;
    private $mailboxFactory;
    private $referenceGenerator;
    private $dumpDir = __DIR__.'/../../var/dumps';
    private $logEntries = [];
    
    public function __construct(
        ManagerRegistry $doctrine,
        ClientFactory $clientFactory,
        MailboxFactory $mailboxFactory,
        ReferenceGenerator $referenceGenerator,
        bool $log)
    {
        $this->doctrine = $doctrine;
        $this->clientFactory = $clientFactory;
        $this->mailboxFactory = $mailboxFactory;
        $this->referenceGenerator = $referenceGenerator;
        $this->log = $log;
    }

    /**
     * Synces all the properties with up-to-date data from Airbnb.
     *
     * No arguments provided: all properties are synced.
     * One argument provided: only the properties of the given Host are synced.
     * Two arguments provided ("Restart from" feature): if $direction is 'after', then the
     *    properties for all the Hosts that are after the given Host in the list will be synced.
     *
     * @param string|null $hostEmailAlias
     * @param string $direction only|after
     */
    public function syncAllProperties(string $hostEmailAlias = null, $direction = "only")
    {
        if (null != $hostEmailAlias && $direction == "only") {
            $host = $this->doctrine->getRepository('App\Entity\Host')->findOneByWithFilter(['wehostEmailAlias' => $hostEmailAlias], null);
            if (null == $host) {
                throw new \Exception("No such host: $hostEmailAlias");
            }
            $properties = $host->getProperties();

        } else {
            $properties = $this->doctrine->getRepository('App\Entity\Property')->findAllWithFilter(null);
        }

        // Add this job in the database
        $cron = new Cron("AirBNB sync iCal");
        $this->doctrine->getManager()->persist($cron);
        $this->doctrine->getManager()->flush();
        
        $this->log("Starting sync at: ".(new \DateTime())->format("r"));
		
        $failures = [];
        $skip = ($direction == 'after' || $direction == 'failures-after');
        foreach ($properties as $property) {

            // skip logic to allow to restart "after" (including) a specific host
            if ($hostEmailAlias != null && $property->getHost()->getWehostEmailAlias() == $hostEmailAlias && $skip) {
                $skip = false;
            }
            if ($skip) {
                continue;
            }

            // skip properties that are not managed by WeHost
            if(! $property->isCurrentlyManaged()) {
                continue;
            }

            try {
                $this->syncProperty($property);

            } catch(\Exception $e) {
                // when syncing a single Host, we want the full exception to surface
                if($hostEmailAlias != null && $direction == 'only') {
                    throw new \Exception("Failed", 0, $e);

                // when syncing a list, don't stop on failure but summarize at the end
                } else {
                    throw new \Exception("First error, stopping", 0, $e);
                    $failures[] = [$property, $e];
                }
            }

            //sleep(1);
        }

        // Update this job in the database
        $cron->setDetails($this->logEntries);
        $cron->setFinished(true);
        $this->doctrine->getManager()->persist($cron);
        $this->doctrine->getManager()->flush(); 
        
        $summary = implode("\n", array_map(
            function($e) {
                return ' * '.$e[0]->getHost()->getName()
                      .' ('.$e[0]->getLabel().')'
                      .' => '.$this->getMessagesFromException($e[1]);
            },
            $failures
        ));
        $this->log(count($properties)." synced, with ".count($failures)." failures:\n$summary");
    }

    /**
     * Synces one specific Property with Airbnb.
     *
     * @param Property $property
     * @throws \Exception
     */
    public function syncProperty(Property $property)
    {
        $em = $this->doctrine->getManager();

        $this->resetLog();
        $this->log("* Property: {$property->getLabel()} ({$property->getAirbnbId()}) - ({$property->getHost()->getWehostEmailAlias()})");

        try {

            // make sure that we got the url of the calendar
            if (null == $property->getAirbnbCalendarUrl()) {
                $this->log('  Calendar URL not defined');
				return;
            }

            // then retrieve the calendar (using unauthenticated client)
            $raw = $this->clientFactory->createClient()->request('GET', $property->getAirbnbCalendarUrl())->getBody();
            $cal = new \om\IcalParser();
            $cal->parseString($raw);

            // create or update all events
            $ids = [];
            foreach ($cal->getSortedEvents() as $event) {
                $eventDetails = $this->getEventDetails($event);
                if (true === $eventDetails->skip) {
                    //$this->log("  !! Skipping: ".$eventDetails->summary);
                    continue;
                }
                
                $reservation = $this->createOrUpdateReservation($property, $eventDetails);
                $ids[] = $reservation->getAirbnbId();
            }

            // and cancel events that are not in the calendar any more
            // note: an event that is cancelled may still have a code if there were cancellation charges
            $reservationsToCancel = $em->getRepository('App\Entity\Reservation')->findByPropertyNotMatchingIds($property, $ids);
            foreach ($reservationsToCancel as $reservation) {
                if ($reservation->getStatus() == Reservation::STATUS_CANCELED) continue;
                $reservation->clearPricingDetails();
                $reservation->setStatus(Reservation::STATUS_CANCELED);
                $this->log("  ".$reservation->getAirbnbId().': '.$reservation->getStatus());
                $em->persist($reservation);
                $em->flush();
                $em->detach($reservation);
            }

            $property->setLastSuccessfulAirbnSync(new \DateTime);

        } catch(\Exception $e) {
            $message = '';
            $this->log("Error. $message\n".$this->getMessagesFromException($e));

            throw new \Exception("Error. $message", 0, $e);

        } finally {
            $property->setLastAirbnbSyncLog($this->getCurrentLog());
            $em->persist($property);
            $em->flush();
        }
    }

    /**
     * Creates or update the reservation with information retrieved from the calendar of the Property.
     *
     * @param Property $property
     * @param \stdClass $event array returned by getEventDetails()
     * @return Reservation a persisted Reservation, up-to-date with latest Airbnb data.
     */
    public function createOrUpdateReservation(Property $property, \stdClass $event): Reservation
    {
        $em = $this->doctrine->getManager();

        // create the reservation if it does not exist
        $reservation = $em->getRepository('App\Entity\Reservation')->findOneByWithFilter(['airbnbId' => $event->code], null);
        if(null == $reservation) {
            $reservation = new Reservation($this->referenceGenerator->generate(), $property, $event->checkin, $event->checkout);
            $reservation->setGuestName($event->guestName);
            $reservation->setGuestPhoneNumber($event->guestPhoneNumber);
            $reservation->setAirbnbId($event->code);
            $reservation->setStatus(true === $event->pending ? Reservation::STATUS_PENDING : Reservation::STATUS_CONFIRMED);
        }

        // transition status from 'pending' to 'confirmed'
        if($reservation->getStatus() == Reservation::STATUS_PENDING && $event->pending == false) {
            $reservation->setStatus(Reservation::STATUS_CONFIRMED);
            $reservation->setCheckinDate($event->checkin);
            $reservation->setCheckoutDate($event->checkout);
        }

        $this->log("  $event->summary (".$event->checkin->format('Y-m-d')."), {$reservation->getStatus()}, profit: ". ($reservation->getHostProfitAmount() ?? "NC"));

        // set the phone number for legacy reservations
        if(empty($reservation->getGuestPhoneNumber())) {
            $reservation->setGuestPhoneNumber($event->guestPhoneNumber);
        }
        
        // set the cleaning fee to default value if zero or null
        if (null == $reservation->getGuestCleaningFee())
        {
            $defaultCleaningFee = $reservation->getProperty()->getDefaultGuestCleaningFee();
            if(null != $defaultCleaningFee) {
                $reservation->setGuestCleaningFee($defaultCleaningFee);
            }
        }
      
        $em->persist($reservation);
        $em->flush();

        $em->detach($reservation);

        return $reservation;
    }

    /**
     * Helper to parse iCal events
     */
    protected function getEventDetails(array $event)
    {
        $details = new \stdClass();

        $details->checkin = $event['DTSTART'];
        $details->checkout = $event['DTEND'];
        $details->summary = $event['SUMMARY'];

        $summaryPattern = "#^(.*) \\(([A-Z0-9]*)\\)$#";
        if(1 !== preg_match($summaryPattern, $details->summary, $summaryMatches)) {
            $details->skip = true;
        } else {
            $details->skip = false;
            $details->guestName = $summaryMatches[1];
            $details->code = $summaryMatches[2];

            if (1 == preg_match("#^PHONE: (.*)$#m", $event['DESCRIPTION'], $phoneMatches)) {
                $details->guestPhoneNumber = $phoneMatches[1] ;
            } else {
                $details->guestPhoneNumber = '';
            }

            $details->pending = substr($details->summary, 0, strlen("PENDING: ")) == "PENDING: " ;
        }

        return $details;
    }

    protected function log(string $message): void
    {
        $this->logEntries[] = $message;
        if($this->log) {
            echo "$message\n";
        }
    }

    protected function resetLog(): void
    {
        $this->logEntries = [];
    }

    protected function getCurrentLog(): string
    {
        return implode("\n", $this->logEntries);
    }

    protected function makeDumpDir()
    {
        if(! is_dir($this->dumpDir)) {
            if(! mkdir($this->dumpDir)) {
                throw new \Exception("Could not create dump dir: $this->dumpDir)");
            }
        }
        return $this->dumpDir;
    }

    protected function dump($contents, $prefix): string
    {
        $tmpfile = tempnam($this->makeDumpDir(), $prefix);
        file_put_contents($tmpfile, $contents);
        return $tmpfile;
    }
    
    protected function getMessagesFromException(\Exception $e)
    {
        return $e->getMessage() . "\n" . ($e->getPrevious() == null ? "" : $this->getMessagesFromException($e->getPrevious()));
    }
}
