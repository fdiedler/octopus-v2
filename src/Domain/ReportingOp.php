<?php

namespace App\Domain;

use Money\Money;
use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Entity\Reservation;
use App\Entity\Cleaning;
use App\Entity\ExtraCharge;
use App\Entity\Market;
use App\Entity\Property;
use App\Security\UserContext;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class ReportingOp
{
    private $cleaningRepository;
    private $reservationRepository;
    private $extraChargesRepository;
    private $marketRepository;
    private $checkinRepository;
    
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->reservationRepository = $doctrine->getRepository('App\Entity\Reservation');
        $this->extraChargesRepository = $doctrine->getRepository('App\Entity\ExtraCharge');
        $this->checkinRepository = $doctrine->getRepository('App\Entity\Checkin');
        $this->cleaningRepository = $doctrine->getRepository('App\Entity\Cleaning');
        $this->marketRepository = $doctrine->getRepository('App\Entity\Market');
        $this->propertyRepository = $doctrine->getRepository('App\Entity\Property');
    }

    public function getReportingCleaningForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $cleanings = $this->cleaningRepository->findCleaningForPeriod($start, $end, $userContext);
        return $this->formatCleaningData($cleanings);
    }
    
    private function formatCleaningData($cleanings)
    {
        $data = array();
        foreach ($cleanings as $cleaning)
        {
            // Check if this reservation associated to this cleaning is canceled
            $resa = $cleaning->getReservation();
            if ($resa != null && $resa->getStatus() == Reservation::STATUS_CANCELED)
                continue;
            
            $data[] = array(
                "id" => $cleaning->getId(),
                "date" => $cleaning->getDate(),
                "comments" => $cleaning->getComments(),
                "type" => $cleaning->getCorrelType(),
                "amount" => $cleaning->getAmountWithoutTaxes(),
                "reservationId" => ($cleaning->getReservation() ? $cleaning->getReservation()->getAirbnbId() : ""),
                "city" => $cleaning->getProperty()->getMarket()->getName(),
                "property" => $cleaning->getProperty(),
                "presta" => $cleaning->getPresta(),
            );
        }
        
        //print_r($data);
        
        return $data;
    }
    
    private function formatCheckinsData($checkins)
    {
        $data = array();
        foreach ($checkins as $checkin)
        {
            $data[] = array(
                "checkin" => $checkin,
                "property" => $checkin->getReservation()->getProperty(),
                "reservation" => $checkin->getReservation(),
            );
        }
        
        //print_r($data); die();
        
        return $data;
    }
    
    private function formatResaData($reservations)
    {
        $data = array();
        foreach ($reservations as $reservation)
        {
            $data[] = array(
                "property" => $reservation->getProperty(),
                "reservation" => $reservation,
            );
        }
        
        //print_r($data);
        
        return $data;
    }
    
    public function getReportingCheckinsForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $checkins = $this->checkinRepository->findByPeriod($start, $end, $userContext);
        return $this->formatCheckinsData($checkins);
    }
    
    public function getReportingResaByCheckinDateForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $reservations = $this->reservationRepository->findManagedByPeriodAndStatuses($start, $end, [Reservation::STATUS_CONFIRMED], $userContext);
        return $this->formatResaData($reservations);
    }
    
    public function getReportingResaByCheckoutDateForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $reservations = $this->reservationRepository->findManagedByPeriodAndStatuses($start, $end, [Reservation::STATUS_CONFIRMED], $userContext, true);
        return $this->formatResaData($reservations);
    }
    
    private function formatExtraChargeData($extras)
    {
        $data = array();
        foreach ($extras as $extra)
        {
            $data[] = array(
                "id" => $extra->getId(),
                "label" => $extra->getLabel(),
                "amount" => $extra->getAmountWithoutTaxes(),
                "date" => $extra->getDate(),
                "comments" => $extra->getComments(),
                "property" => $extra->getProperty(),
                "city" => $extra->getProperty()->getMarket()->getName(),
            );
        }
        
        //print_r($data);
        
        return $data;
    }
    
    public function getReportingExtraChargeForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $extra = $this->extraChargesRepository->findByPeriod($start, $end, $userContext);
        return $this->formatExtraChargeData($extra);
    }
    
    public function getManagedReservationPerMarket(DateTime $startDate, DateTime $endDate, UserContext $userContext)
    {
        $nbReservation = array();
        $markets =  $this->marketRepository->findAllWithFilter($userContext);
        foreach ($markets as $market)
        {
            // Find the right objective according to the current month
            $nbresaObjective = 0;
            foreach ($market->getObjectives() as $marketObjective)
            {
                if ($startDate->format("m") == $marketObjective->getDate()->format("m"))
                {
                    // An objective has been set for this month
                    $nbresaObjective = $marketObjective->getValue();
                    break;
                }
            }
           
            $managedReservationsPerMarket = $this->reservationRepository->findManagedByPeriodAndMarket($startDate, $endDate, $market->getId(), $userContext);
            $nbReservation[$market->getId()] = ["nbresa" => count($managedReservationsPerMarket), "nbresaObjective" => $nbresaObjective];
        }   
        
        return $nbReservation;
    }
    
    private function checkDuplicateProperties(Market $market, UserContext $userContext)
    {
        $properties = $this->propertyRepository->findByWithFilter(["market" => $market], $userContext);
        $tmp = [];
        foreach ($properties as $property)
        {
            $tmp[$property->getId()] = $property->getLabel();
        }
        $duplicateProperties = array_intersect($tmp, array_unique(array_diff_key($tmp, array_unique($tmp))));
        
        $duplicateData = [];
        foreach ($duplicateProperties as $duplicatePropertyId => $duplicatePropertyName)
        {
            $reservations = $this->reservationRepository->findByWithFilter(["property" => $duplicatePropertyId], $userContext);
            $duplicateData[$duplicatePropertyName][$duplicatePropertyId] = count($reservations);
        }
        
        return $duplicateData;
    }
    
    public function getWarningsAboutOperational(UserContext $userContext)
    {
        $alerts = array("warning" => [], "error" => []);
        
        // Check property uniqueless
        $markets =  $this->marketRepository->findAllWithFilter($userContext);
        foreach ($markets as $market)
        {
            $duplicateData = $this->checkDuplicateProperties($market, $userContext);
            foreach ($duplicateData as $key => $value)
            {
                $alerts["error"]["duplicate"][$key] = $value;
            }
        }
        
        // Check AirBNB calendar
        $properties = $this->propertyRepository->findByWithFilter(["airbnbCalendarUrl" => null], $userContext);
        foreach ($properties as $property)
        {
            $label = "(".$property->getMarket()->getName().") ".$property->getLabel();
            $alerts["warning"]["calendar"][$property->getId()] = $label;
        }
        
        // Check cleaning date
        $reservations = $this->reservationRepository->findCleaningBeforeCheckout($userContext);
        foreach ($reservations as $reservation)
        {
            $label = "(".$reservation->getProperty()->getMarket()->getName().") ".$reservation->getProperty()->getLabel()." - Checkout date : ".$reservation->getCheckoutDate()->format("Y-m-d");
            $alerts["warning"]["cleaning"][$reservation->getCleanings()[0]->getId()] = $label;
        }
        
        // Check cleaning provider and cleaning type
        $cleanings = $this->cleaningRepository->findAllWithFilter($userContext);
        foreach ($cleanings as $cleaning)
        {
            $label = "(".$cleaning->getProperty()->getMarket()->getName().") ".$cleaning->getProperty()->getLabel();
            if ($cleaning->getType() == Cleaning::CLEANING_DATA["NoCleaning"]["type"]
                    && $cleaning->getPresta() !== null)
            {
                $alerts["warning"]["cleaningPresta"][$cleaning->getId()] = $label;
            }
            else if ($cleaning->getType() != Cleaning::CLEANING_DATA["NoCleaning"]["type"]
                    && $cleaning->getPresta() === null)
            {
                $alerts["warning"]["cleaningPresta"][$cleaning->getId()] = $label;
            }
        }
        
        // Check if cleanings / checkin / checker are scheduled for incoming reservations between current date and tomorrow 
        // Do not check before 12am (the time of the server is out of sync of -1 hour)
        if (date('H') > 13)
        {
            $tomorrowDate = new \DateTime("tomorrow");
            $start = (clone $tomorrowDate)->sub(new \DateInterval('P1D'));
            $reservations = $this->reservationRepository->findAllManagedWithNoCleaning($start, $tomorrowDate, $userContext);
            foreach ($reservations as $reservation)
            {
                $label = "(".$reservation->getProperty()->getMarket()->getName().") ".$reservation->getProperty()->getLabel();
                $alerts["warning"]["reservationWithNoCleaning"][$reservation->getId()] = $label;
            }
            
            $reservations = $this->reservationRepository->findAllManagedWithNoCheckin($start, $tomorrowDate, $userContext);
            foreach ($reservations as $reservation)
            {
                $label = "(".$reservation->getProperty()->getMarket()->getName().") ".$reservation->getProperty()->getLabel();
                $alerts["warning"]["reservationWithNoCheckin"][$reservation->getId()] = $label;
            }
        }
        
        // Check if cleanings / checkin / checker are scheduled for the current date
        $nowDate = new \DateTime("now");
        $nowDate->setTime(0,0,0);
        $reservations = $this->reservationRepository->findAllManagedWithNoCleaning($nowDate, $nowDate, $userContext);
        foreach ($reservations as $reservation)
        {
            $label = "(".$reservation->getProperty()->getMarket()->getName().") ".$reservation->getProperty()->getLabel();
            $alerts["error"]["reservationWithNoCleaning"][$reservation->getId()] = $label;
        }
        
        $reservations = $this->reservationRepository->findAllManagedWithNoCheckin($nowDate, $nowDate, $userContext);
        foreach ($reservations as $reservation)
        {
            $label = "(".$reservation->getProperty()->getMarket()->getName().") ".$reservation->getProperty()->getLabel();
            $alerts["error"]["reservationWithNoCheckin"][$reservation->getId()] = $label;
        }
        
        return $alerts;
    }
}
