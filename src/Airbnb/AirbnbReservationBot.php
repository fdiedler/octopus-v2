<?php

namespace App\Airbnb;

use App\Entity\Cron;
use App\Entity\Host;
use App\Entity\Property;
use App\Entity\Reservation;
use App\Mail\InboxReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use DateTime;

/**
 * Bot to gather information about reservation from Airbnb. The bot gets reservation prices from a messaging.
 *
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class AirbnbReservationBot
{
    private $doctrine;
    private $logEntries = [];
    private $inboxReader;
    private $inboxReaderAlt = [];
    
    public function __construct(
        ManagerRegistry $doctrine,
        InboxReader $_inboxReader,
        bool $log)
    {
        $this->doctrine = $doctrine;
        $this->inboxReader = $_inboxReader;
        $this->log = $log;
    
        $this->inboxReader->openConnectionInbox();
    
        // Hack should be removed
        $this->inboxReaderAlt[0] = new InboxReader("imap.gmail.com", "reservation@wehost.fr", "resa1015");;
        $this->inboxReaderAlt[0]->openConnectionInbox();
        
        $this->inboxReaderAlt[1] = new InboxReader("imap.gmail.com", "reservationplus@wehost.fr", "resaplus0818");;
        $this->inboxReaderAlt[1]->openConnectionInbox();
                
        $this->inboxReaderAlt[2] = new InboxReader("imap.gmail.com", "resa.bordeaux@wehost.fr", "bordeaux1017");;
        $this->inboxReaderAlt[2]->openConnectionInbox();
    }

    protected function log(string $message): void
    {
        $this->logEntries[] = $message;
        if($this->log) {
            echo "$message\n";
        }
    }
    
    // Extract the amount of a given reservation reference
    public function getAmountAfterText($plainText, $text)
    {
        // see tests/Unit/Airbnb/AirbnbResaBot for more details

        $pos = strpos($plainText, $text);
        if ($pos == false)
            return false;
        
        // Extract the right part according to airbnb reservation reference
        $substring = substr($plainText,$pos);
        
        return $this->extractAmount($substring);
    }
    
    // Extract a float number from a string
    private function extractAmount($string)
    {
        // Find the amount of this reservation
        if(! preg_match("/\d+\.\d{1,2}/", $string, $matches)) {
            return false;
        }
        
        // We found the amount !
        $amount = $matches[0];
        
        return $amount;
    }
    
    // Extract the number of reservation from a string
    public function getNbReservation($string)
    {
        // Find string beginning with HM + exactly 8 characters
        if(! preg_match_all("/HM\w{8}/", $string, $matches)) {
            return false;
        }
        
        return count($matches[0]);
    }
    
    // Synchronise one reservation price
    public function syncOneReservationPrice(Reservation $reservation)
    {
        $airbnbRef = $reservation->getAirbnbId();
        
        if ($airbnbRef == "")
        {
            $this->log("AirBnb reference empty !");
            return false;
        }
                
        // Check if this reservation has already been treated
        if ($reservation->getHostProfitAmount() != null && $reservation->getHostProfitAmount() > 0)
        {
            $this->log("Amount for reservation $airbnbRef already set !");
            return true;
        }
        
        $this->log("Synchronizing $airbnbRef reservation from [".$reservation->getProperty()->getMarket()->getName()."] ...");
        
        // Try to open the IMAP server
        //$this->inboxReader->openConnectionInbox();
    
        // Search message according to criterias (here the airbnb reference)
        $messages = $this->inboxReader->searchAllMailBoxes(null, 'BODY "'.$airbnbRef.'"', true);
        $this->log("   Got ".count($messages)." message(s)"); 
        
        // Hack : Search in alternative mailboxes for Paris and Bordeaux...
        if (count($messages) == 0)
        {
            foreach ($this->inboxReaderAlt as $mailbox)
            {
                $messages = $mailbox->searchAllMailBoxes(array("! Versements", "! Versements Airbnb", "[Gmail]/Corbeille"), 'BODY "'.$airbnbRef.'"', false);
                $this->log("   Got ".count($messages)." message(s) [from alternative mailbox]"); 
                
                if (count($messages) > 0)
                {
                    // Just a hack, should be remove next month...
                    $ok = false;
                    foreach($messages as $message)
                    {
                        $mailSubject = imap_utf8($message["subject"]);
                        if (strpos($mailSubject, "Payout") === false && strpos($mailSubject, "Versement de") === false) {}
                        else 
                        {
                            $ok = true;
                            break;
                        }
                    }
                    
                    if ($ok) break;
                }
            }
        }
        // ***************************************************************************
        
        if (count($messages) > 0)
        {
            $amount = 0;
            foreach($messages as $message)
            {
                // Check the email subject and filter them to read only payout mails 
                $mailSubject = imap_utf8($message["subject"]);
                if (strpos($mailSubject, "Payout") === false && strpos($mailSubject, "Versement de") === false)
                {
                    $this->log("\tDiscard email with subject [$mailSubject]");
                    continue;
                }
                
                // Try to find the reservation amount
                $amount = $this->getAmountAfterText($message["plaintext"], $airbnbRef);
                if ($amount == false)
                {
                    $this->log("\tUnable to retrieve amount for this reservation {$airbnbRef}. Substring : {$message['plaintext']}");
                    continue;
                }
                
                break;
            }
            
            if ($amount > 0)
            {
                $discountStr = "Resolution Adjustment";
                
                // Get the number of reservations in this message
                $nbResa = $this->getNbReservation($message["plaintext"]);
                
                // Count the number of discounts in this message
                $nbAdjustmentAmount = substr_count($message["plaintext"], $discountStr);
                if ($nbAdjustmentAmount > 1 || ($nbResa > 1 && $nbAdjustmentAmount > 0))
                {
                    // We cannot decide... set a pricing of €0
                    $amount = 0;
                    $this->log("\tCannot deduce amount for reservation $airbnbRef\n"); 
                }
                else
                {
                    // Sometimes we have a discount from AirBNB...
                    $adjustmentAmount = $this->getAmountAfterText($message["plaintext"], $discountStr);
                    if ($adjustmentAmount != false)
                    {
                        $this->log("\tGot ".($amount - $adjustmentAmount)." ($amount - $adjustmentAmount) (for reservation $airbnbRef\n"); 
                    
                        // Set the discount amount
                        $reservation->setDiscountAmount($adjustmentAmount * 100);
                    
                        // Apply the discount on the reservation amount
                        //$amount -= $adjustmentAmount;
                    }
                    else
                    {
                        $this->log("\tGot $amount for reservation $airbnbRef\n"); 
                    }
                }
                
                $reservation->setHostProfitAmount($amount * 100);
                $this->doctrine->getManager()->persist($reservation);
                $this->doctrine->getManager()->flush();
                $this->doctrine->getManager()->detach($reservation); 
                return true;
            }
        }
        else
        {
            $this->log("\tCannot got amount for reservation $airbnbRef\n"); 
        }
        
        return false;
    }
    
    // Synchronise all reservations between startPeriod and endPeriod
    public function syncAllReservationPrice($startPeriod, $endPeriod)
    {
        $em = $this->doctrine->getManager();
        
        $startPeriod = new DateTime($startPeriod);
        $endPeriod = new DateTime($endPeriod);
        
        $userContext = null;
        $reservations = $em->getRepository('App\Entity\Reservation')->findManagedByPeriodAndStatuses($startPeriod, $endPeriod, [Reservation::STATUS_CONFIRMED], $userContext);
        
        $this->log("**** ".count($reservations)." reservations need to be synced **** ");
        
        // Add this job in the database
        $cron = new Cron("AirBNB sync prices");
        $em->persist($cron);
        $em->flush();
        
        try
        {
            $cpt = 0;
            $notFoundReservation = array();
            foreach ($reservations as $reservation)
            {
                ++$cpt;
                $this->log("[".$cpt." / ".count($reservations)."]");
                
                // Get the city from the reservation
                //$reservationCity = $reservation->getProperty()->getMarket()->getName();
                //if ($reservationCity != "Paris") continue;
                //if ($reservation->getAirbnbId() != "HM8MSEP5EH") continue;
                
                // Retrieve the amount of this reservation
                if (!$this->syncOneReservationPrice($reservation))
                {
                    $notFoundReservation[] = $reservation->getAirbnbId();
                }
            }
        }
        catch(\Exception $ex)
        {
            echo $ex->getMessage();
        }
        
        // Indicate that the job is finished
        $cron->setDetails($this->logEntries);
        $cron->setFinished(true);
        $em->persist($cron);
        $em->flush(); 
    
        // Print debug
        echo count($notFoundReservation)." reservation failed over ".count($reservations)."!\n";    
        print_r($notFoundReservation);
    }
}
