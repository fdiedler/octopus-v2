<?php

namespace App\FullCalendar;

use Toiba\FullCalendarBundle\Entity\Event;
use Toiba\FullCalendarBundle\Event\CalendarEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Market;
use App\Entity\Reservation;
use App\Entity\Property;
use App\Http\IcalManager;

class FullCalendarListener
{
    /**
     * @var RequestStack
     */
    protected $requestStack;
    
    /**
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * @var UrlGeneratorInterface
     */
    private $router;
    
    private $icalManager;
    private $userContext;
    private $startDate;
    private $endDate;
    
    // Just for debug purpose
    private $debug = false;
    
    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router, RequestStack $requestStack, IcalManager $icalManager, SessionInterface $session)
    {
        $this->em = $em;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->icalManager = $icalManager;
        $this->userContext = $session->get('userContext');
    }
    
    private function writeLog($data)
    {
        if ($this->debug) 
            file_put_contents("Log-FullCalendarListener.txt", $data, FILE_APPEND);
    }
    
    private function createCalendarEvent($title, $startDate, $endDate, $market, $ressourceId, $status = false)
    {
        $event = new Event($title, $startDate, $endDate);
        
        $event->setAllDay(true);
        $event->setCustomField('resourceId', $ressourceId);
        $event->setCustomField('market', $market);
        $event->setCustomField('status', $status);
        
        if ($status == false)
        {
            // Blocked event
            $event->setColor("#CCC");
        }
        else if ($status == Reservation::STATUS_CONFIRMED)
        {
            $event->setColor("#0AC254");
        }
        else if ($status == Reservation::STATUS_PENDING)
        {
            $event->setColor("#FFA500");
        }
        else if ($status == Reservation::STATUS_CANCELED)
        {
            $event->setColor("#F00");
        }
        
        return $event;
    }
    
    private function addCalendarBlockedEvents(CalendarEvent $calendar, Property $property)
    {
        $blockedEvents = $this->icalManager->getBlockedEvents($property->getAirbnbCalendarUrl(), $this->startDate, $this->endDate);
        foreach($blockedEvents as $blockedEvent)
        {  
            $this->writeLog($blockedEvent->summary);
                $event = $this->createCalendarEvent(
                    $blockedEvent->summary, $blockedEvent->beginDate, $blockedEvent->endDate,
                    $property->getMarket()->getName(), $property->getId());
                
                $calendar->addEvent($event);
            $this->writeLog("done\n");
        }
    }
    
    private function addCalendarReservationEvents(CalendarEvent $calendar, $reservations)
    {
        foreach($reservations as $resa)
        {
            // Get only confirmed and pending reservations
            if ($resa->getStatus() == Reservation::STATUS_CANCELED)
                continue;
            
            $event = $this->createCalendarEvent(
                $resa->getGuestName(), $resa->getCheckinDate(), $resa->getCheckoutDate(), 
                $resa->getProperty()->getMarket()->getName(), $resa->getProperty()->getId(), $resa->getStatus());
            
            $event->setCustomField('amount', $resa->getHostProfitAmount());
            
            $calendar->addEvent($event);
        }
    }
    
    private function addCalendarEventsByProperty(CalendarEvent $calendar, Property $property)
    {
        // Get periods where the property is not available
        $this->addCalendarBlockedEvents($calendar, $property);
       
        // Get all managed reservations form a given period
        $reservations = $this->em->getRepository(Reservation::class)->findManagedByPropertyAndPeriod(
            $property, $this->startDate, $this->endDate, $this->userContext);
        $this->writeLog("[".$property->getMarket()->getName()."] Flat".$property->getLabel()." -> ".count($reservations)."\n");
        
        $this->addCalendarReservationEvents($calendar, $reservations);
    }
    
    private function addCalendarEventsByMarket(CalendarEvent $calendar, Market $market)
    {
        foreach($market->getProperties() as $property)
        {
            if ($property->isCurrentlyManaged())
                $this->addCalendarEventsByProperty($calendar, $property);
        }
    }
    
    private function addCalendarEvents(CalendarEvent $calendar)
    {
        $markets =  $this->em->getRepository(Market::class)->findAllWithFilter($this->userContext);
        foreach($markets as $market)
        {
            $this->addCalendarEventsByMarket($calendar, $market);
        }
    }
    
    public function loadEvents(CalendarEvent $calendar)
    {  
        $request = $this->requestStack->getCurrentRequest();
    
        $this->startDate = $calendar->getStart();
        $this->endDate = (clone $calendar->getEnd())->sub(new \DateInterval('P1D')); // because fullcalendar send the first day of the next month
        $filters = $calendar->getFilters();
        
        $this->addCalendarEvents($calendar);
        
        /*$propertyId =  $request->get('propertyId');
        $marketId =  $request->get('marketId');
        $mode = $request->get('mode');
        
        if ($mode === 'property')
        {
            $property = $this->em->getRepository(Property::class)->findOneByWithFilter(["id" => $propertyId], null);
            $this->addCalendarEventsByProperty($calendar, $property);
        }
        else if ($mode === 'market')
        {
            $market = $this->em->getRepository(Market::class)->findOneByWithFilter([], null);
            $this->addCalendarEventsByMarket($calendar, $market);
        }
        else
        {
            $this->addCalendarEvents($calendar);
        }*/
    }
}