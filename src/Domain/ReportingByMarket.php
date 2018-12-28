<?php

namespace App\Domain;

use App\Entity\Reservation;
use App\Entity\Cleaning;
use App\Entity\Property;
use App\Entity\Market;
use App\Entity\Checker;
use App\Entity\Checkin;

use Money\Money;
use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class ReportingByMarket
{
    private $emr;
    private $finance;
    private $marketRepository;
    private $reservationRepository;
    private $extraChargesRepository;
    private $cleaningRepository;
    private $checkerRepository;
    private $checkinRepository;
    private $userContext;
    
    public function __construct(Finance $finance, ManagerRegistry $doctrine, SessionInterface $session)
    {
        $this->emr = $doctrine;
        $this->finance = $finance;
        $this->marketRepository = $doctrine->getRepository('App\Entity\Market');
        $this->reservationRepository = $doctrine->getRepository('App\Entity\Reservation');
        $this->extraChargesRepository = $doctrine->getRepository('App\Entity\ExtraCharge');
        $this->cleaningRepository = $doctrine->getRepository('App\Entity\Cleaning');
        $this->checkerRepository = $doctrine->getRepository('App\Entity\Checker');
        $this->checkinRepository = $doctrine->getRepository('App\Entity\Checkin');
        $this->userContext = $session->get('userContext');
    }
    
    public function getCheckinReportingByMarketForPeriod(string $yearMonth)
    {
        $start = new \DateTime("$yearMonth-01");
        $end = new \DateTime("$yearMonth-31");
        $data = [];
        
        $markets =  $this->marketRepository->findAllWithFilter($this->userContext);
        foreach ($markets as $market)
        {
            $data[$market->getName()]["detail"] = [];
            $totalCost = Money::EUR(0);
            $totalCheckins = 0;
            
            // Get checker belonging to this market
            $checkers = $this->checkerRepository->findByWithFilter(["market" => $market], $this->userContext);
            foreach ($checkers as $checker)
            {
                // Compute cost
                $total = Money::EUR(0);
                $checkins = $this->checkinRepository->findByCheckerAndPeriod($start, $end, $checker, $this->userContext);
                foreach ($checkins as $checkin)
                {
                    $total = $total->add($checkin->getAmountWithoutTaxesAsMoney());
                }
                
                if (count($checkins) > 0)
                {
                    $data[$market->getName()]["detail"][] = ["checker" => $checker, "totalCheckins" => count($checkins), "cost" => $total];
                }
                
                // Totals
                $totalCheckins += count($checkins);
                $totalCost = $totalCost->add($total);
            }
            
            $data[$market->getName()]["totalCost"] = $totalCost;
            $data[$market->getName()]["totalCheckins"] = $totalCheckins;
        }
        
        return $data;
    }

    public function createColumnChartByMarketPerYear(string $year, $filterMarketsIds)
    {
        $data = $this->getReportingByMarket($year);
        
        // Create chart labels
        $labels = ["Month"];
        $markets =  $this->marketRepository->findAllWithFilter($this->userContext);
        foreach ($markets as $market)
        {
            // Discard some market 
            if ($filterMarketsIds && !in_array($market->getId(), $filterMarketsIds)) continue;
            
            $labels[] = $market->getName();
        }
        
        // Format chart data
        $chartData = [$labels];
        foreach ($data as $monthId => $statsPerMonth)
        {
            $date = new \DateTime("$year-$monthId-01");
            $row = [];
            $row[] = $date->format("F");
            
            $total = 0;
            foreach ($markets as $market)
            {
                // Discard some market 
                if ($filterMarketsIds && !in_array($market->getId(), $filterMarketsIds)) continue;
                
                $turnover = $statsPerMonth[$market->getName()]["turnover"]->getWithoutTaxes()->getAmount() / 100;
                $row[] = $turnover;
                $total += $turnover;
            }
            
            // Do not display months with no data
            if ($total > 0)
                $chartData[] = $row;
        }
        
        // Create charts
        $chart = $this->createaColumnChart($chartData);
        $chartThumbnail = $this->createaColumnChart($chartData, true);
  
        return ["chart" => $chart, "chartThumbnail" => $chartThumbnail];
    }
    
    private function createaColumnChart($chartData, $thumbnail = false)
    {
        // Create chart
        $chart = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\Material\ColumnChart();
        $chart->getData()->setArrayToDataTable($chartData);

        // Set options 
        $chart->getOptions()
            ->getAnnotations()
            ->setAlwaysOutside(true)
        ;
        
        $chart->getOptions()
            ->setBars('vertical')
            ->setBackgroundColor('#EEE')
            ->getVAxis()
                ->setFormat('short')
                ->setTitle('Turnover (HT)')
        ;
        
        if ($thumbnail)
        {
            $chart->getOptions()->getLegend()->setPosition('none');
            $chart->getOptions()->setHeight(300);
            //$chart->getOptions()->getChartArea()->setHeight(500);
            $chart->getOptions()->getChartArea()->setWidth(300);
        }
        else
        {
            $chart->getOptions()->setHeight(850);
        }
        
        return $chart;
    }
    
    public function getReportingByMarket(string $year)
    {
        $stats = [];
        for ($month = 1; $month <= 12; $month++)
        {
            $start = new \DateTime("$year-$month-01");
            $end = new \DateTime("$year-$month-31");
            $stats[$month] = $this->getReportingByMarketForPeriod($start, $end);
        }
        
        return $stats;
    }
    
    public function getReportingByMarketForPeriod(DateTime $start, DateTime $end)
    {
        $data = [];

        $statuses = [ Reservation::STATUS_CONFIRMED ];
        
        // Set start time date to 00h00:00 otherwise findManagedByPeriodAndStatuses() can skip some reservations...
        $start->setTime(0,0,0);
        
        // Set end time to 23h59:59 otherwise findManagedByPeriodAndStatuses() can skip some reservations...
        $end->setTime(23,59,59);
        
        // Init all amounts for each market
        $markets =  $this->marketRepository->findAllWithFilter($this->userContext);
        foreach ($markets as $market)
        {        
            $this->sumMoney($data, $market, 'turnover', MoneyWithTax::zero());
            $this->sumMoney($data, $market, 'cleaningCosts', MoneyWithTax::zero());
            $this->sumMoney($data, $market, 'managerCommission', Money::EUR(0));
        }
        
        $reservations = $this->reservationRepository->findManagedByPeriodAndStatuses($start, $end, $statuses, $this->userContext);
        foreach($reservations as $reservation) {
            $market = $reservation->getProperty()->getMarket();
            $contractType = $reservation->getProperty()->getType();
            
            // if pricing details were not collected set, discard (same as in InvoiceManager...)
            if($reservation->getHostProfit() == null || $reservation->getHostProfitAmount() == "0" || $reservation->getGuestCleaningFee() == null) {
                continue;
            }
            
            // Should be improved if several cleaning for one reservation...
            $cleanings = $reservation->getCleanings();
            $cleaning = null;
            if (count($cleanings) > 0)
                $cleaning = $cleanings[0];

            $extraKey = $contractType == 1 ? array_keys(Property::TYPE_DATA)[$contractType].'_' : "";
            $this->sumMoney($data, $market, 'turnover', $this->finance->getTurnoverFromReservation($reservation, $cleaning), $extraKey);
            $this->sumMoney($data, $market, 'cleaningCosts', $this->finance->hostCleaningFee($reservation, $cleaning), $extraKey);
            $this->sumMoney($data, $market, 'managerCommission', $this->finance->getManagerCommission($reservation), $extraKey);
            $this->sumBillableHost($data, $market, 'billableHosts', $reservation->getProperty()->getHost()->getId(), $extraKey);
        }

        // Add extra cleaning
        $extraCleanings = $this->cleaningRepository->findExtraCleaningForPeriod($start, $end, $this->userContext);
        foreach($extraCleanings as $extraCleaning)
        {
            $market = $extraCleaning->getProperty()->getMarket();
            $contractType = $extraCleaning->getProperty()->getType();
            $amount = $this->finance->moneyWithDefaultTax($extraCleaning->getAmountWithoutTaxesAsMoney());
            
            //$extraKey = $contractType == 1 ? $labelPlus : "";
            $extraKey = $contractType == 1 ? array_keys(Property::TYPE_DATA)[$contractType].'_' : "";
            $this->sumMoney($data, $market, 'turnover', $amount, $extraKey);
            $this->sumMoney($data, $market, 'cleaningCosts', $amount, $extraKey);
            $this->sumBillableHost($data, $market, 'billableHosts', $extraCleaning->getProperty()->getHost()->getId(), $extraKey);
        }

        // Add extra charges
        $extraCharges = $this->extraChargesRepository->findByPeriod($start, $end, $this->userContext);
        foreach($extraCharges as $extraCharge) {
            $market = $extraCharge->getProperty()->getMarket();
            $contractType = $extraCharge->getProperty()->getType();
            
            //$extraKey = $contractType == 1 ? $labelPlus : "";
            $extraKey = $contractType == 1 ? array_keys(Property::TYPE_DATA)[$contractType].'_' : "";
            $this->sumMoney($data, $market, 'turnover', $extraCharge->getAmountWithTaxes(), $extraKey);
            $this->sumBillableHost($data, $market, 'billableHosts', $extraCharge->getProperty()->getHost()->getId(), $extraKey);
        }
        
        // reduce the 'billableHosts' list to a count
        $data = array_map(
            function($elt) { $elt['billableHosts'] = count(array_unique($elt['billableHosts'] ?? [])); return $elt; },
            $data
        );
        
        $data = array_map(
            function($elt) { 
                $l = array_keys(Property::TYPE_DATA)[1].'_'; 
                $elt[$l.'billableHosts'] = count(array_unique($elt[$l.'billableHosts'] ?? [])); 
                return $elt; 
            },
            $data
        );
        
        return $data;
    }

    private function sumBillableHost(&$data, Market $market, $key, $value, $extraKey = "")
    {
        $data[$market->getName()][$key][] = $value;
        if ($extraKey !== "")
        {
            $data[$market->getName()][$extraKey.$key][] = $value;
        }
    }
    
    private function sumMoney(&$data, Market $market, $key, $value, $extraKey = "")
    {
        if(! isset($data[$market->getName()][$key])) {
            $data[$market->getName()][$key] = $value instanceof MoneyWithTax ? MoneyWithTax::zero() : Money::EUR(0);
        }
        $current = $data[$market->getName()][$key];
        $data[$market->getName()][$key] = $current->add($value); // works with Money and MoneyWithTax
        
        // Add Money in the right section according to contract type
        if ($extraKey !== "")
        {
            if(! isset($data[$market->getName()][$extraKey.$key])) {
                $data[$market->getName()][$extraKey.$key] = $value instanceof MoneyWithTax ? MoneyWithTax::zero() : Money::EUR(0);
            }
            
            $current = $data[$market->getName()][$extraKey.$key];
            $data[$market->getName()][$extraKey.$key] = $current->add($value);
        }
    }

}
