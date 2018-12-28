<?php

namespace App\Http;

use App\Http\ClientFactory;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Helper to parse iCal events
 *
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class IcalManager
{
    private $doctrine;
    private $clientFactory;
    
    public function __construct(ManagerRegistry $doctrine, ClientFactory $clientFactory)
    {
        $this->doctrine = $doctrine;
        $this->clientFactory = $clientFactory;
    }
    
    /**
     * Update all iCal calendar stored in local. This allows to speed up fullCalendar simulating caching.
     *
     * @return array
     */
    public function updateLocalIcal()
    {
        $log = [];
        //$properties = $this->doctrine->getRepository('App\Entity\Property')->findByWithFilter(["market" => 3], null);
        $properties = $this->doctrine->getRepository('App\Entity\Property')->findAllWithFilter(null);
        $ch = curl_init();
        foreach ($properties as $prop)
        {
            if ($prop->getAirbnbCalendarUrl() == null)
                continue;
            
            // Extract airbnb identifier from iCal url
            $data = parse_url($prop->getAirbnbCalendarUrl());
            $airbnbId = basename($data['path'], '.ics');
            if ($airbnbId == "")
            {
                $log[] = $prop->getLabel()." -> Airbnb ID not found in iCal calendar !";
                continue;
            }
            
            echo "Creating for ".$prop->getLabel()." -> ".$airbnbId." ...";
            
            // Get the calendar using a GET request with Curl
            curl_setopt($ch, CURLOPT_URL, $prop->getAirbnbCalendarUrl());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            $output = curl_exec($ch);

            // Write the content of the calendar in a file stored in local
            //$basePath = $this->get('kernel')->getRootdir()."/../public/";
            $basePath = "public/";
            $res = file_put_contents($basePath."ical/$airbnbId.ical", $output);
            if ($res === FALSE)
                $log[] = $prop->getLabel()." -> Failed to write ".$basePath."ical/$airbnbId.ical file !";
            else echo "             DONE\n";
        }
        curl_close($ch);
        
        return $log;
    }
    
    /**
     * Get reservations from an iCal
     *
     * @return array
     */
    public function getReservationEvents($icalUrl)
    {
        $raw = $this->clientFactory->createClient()->request('GET', $icalUrl)->getBody();
        $cal = new \om\IcalParser();
        $cal->parseString($raw);
        
        $events = [];
        foreach ($cal->getSortedEvents() as $event) {
            $eventDetails = $this->getEventDetails($event);
            if (true === $eventDetails->skip) {
                continue;
            }
            
            $events[] = $eventDetails;
        }
        
        return $events;
    }
    
    /**
     * Get blocked date from an iCal
     *
     * @return array
     */
    public function getBlockedEvents($icalUrl, $startDate = null, $endDate = null, $useLocalFile = true)
    {
        // Safe check
        if ($icalUrl == null || $icalUrl == '')
            return [];
        
        if ($useLocalFile)
        {
            // Extract Airbnb ID
            $data = parse_url($icalUrl);
            $number = basename($data['path'], '.ics');
        
            // Read local iCal file
            $raw = @file_get_contents("ical/$number.ical");
            if ($raw === FALSE)
                return [];
        }
        else
        {
            $raw = $this->clientFactory->createClient()->request('GET', $icalUrl)->getBody();
        }
        
        $cal = new \om\IcalParser();
        $cal->parseString($raw);
        
        $events = [];
        foreach ($cal->getSortedEvents() as $event) {
            // Discard event not in a range period
            $shouldChecked = ($startDate == null || $endDate == null);
            if ($shouldChecked || $event['DTSTART'] >= $startDate && $event['DTSTART'] <= $endDate)
            {
                $eventDetails = $this->getBlockedEventDetails($event);
                if (true === $eventDetails->skip) {
                    continue;
                }
                $events[] = $eventDetails;
            }
        }
        
        return $events;
    }

    /**
     * Helper to parse iCal blocked events
     */
    private function getBlockedEventDetails(array $event)
    {
        $details = new \stdClass();

        $details->beginDate = $event['DTSTART'];
        $details->endDate = $event['DTEND'];
        $details->summary = $event['SUMMARY'];
        
        // Reservations have a LOCATION field
        // If this field is missing, it is a block range defined by the Host
        if (isset($event['LOCATION']))
        {
            $details->skip = true;
        }
        else $details->skip = false;
        
        return $details;
    }
    
    /**
     * Helper to parse iCal events
     */
    private function getEventDetails(array $event)
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
}
