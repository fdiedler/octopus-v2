<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class FrontController extends Controller
{

    /**
     * @Route("/userPreferences", name="user_preference")
     * @Security("has_role('ROLE_USER')")
     */
    public function userPreferencesAction(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $preferences = [];
            $currentUser = $this->get('security.token_storage')->getToken()->getUser();
            
            $dashboardDisplayCheckout = $request->request->get("dashboard_display_checkout");
            $dashboardDisplayCurrentDate = $request->request->get("dashboard_display_current_date");
            
            if ($dashboardDisplayCheckout == "on")
            {
                $preferences["dashboard_display_checkout"] = true;
            }
            if ($dashboardDisplayCurrentDate == "on")
            {
                $preferences["dashboard_display_current_date"] = true;
            }
            
            $currentUser->setPreferences($preferences);
            $this->getDoctrine()->getManager()->persist($currentUser);
            $this->getDoctrine()->getManager()->flush();
            
            return $this->redirect("/dashboard");
        }
        
        return $this->render('userPreferences.html.twig', [
            
        ]);
    }
    
    /**
     * @Route("/dashboard/{date}/{marketId}", name="dashboard")
     */
    public function dashboardAction($date = 'tomorrow', $marketId = '')
    {
        $userContext = $this->get('session')->get('userContext', null);
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        
        // Change default date according to user's settings 
        if (isset($currentUser->getPreferences()["dashboard_display_current_date"]) && $date == 'tomorrow')
            $date = date("Y-m-d"); // do not use "now" because we need a time set to 00h00:00
        
        $currentDate = new \DateTime($date);

        $markets =  $this->getDoctrine()->getRepository('App\Entity\Market')->findAllWithFilter($userContext);

        if ($currentUser->isOutsideUser())
        {
            // Get list of checkin for a given period for Checkers
            $listCheckins = [];
            if ($currentUser->hasCheckerAccess())
                $listCheckins = $this->get('App\Domain\ReportingOutside')->getReportingCheckinsForPeriod($currentDate, $currentDate, $userContext);
        
            // Get list of cleanings for a given period for Cleaning providers
            $listCleanings = [];
            if ($currentUser->hasCleaningAccess())
                $listCleanings = $this->get('App\Domain\ReportingOutside')->getReportingCleaningForPeriod($currentDate, $currentDate, $userContext);
        
            return $this->render('outside/dashboard.html.twig', [
                'previousDate'      => (clone $currentDate)->sub(new \DateInterval('P1D'))->format('Y-m-d'),
                'nextDate'          => (clone $currentDate)->add(new \DateInterval('P1D'))->format('Y-m-d'),
                'selectedMarket'    => $markets[0],
                'currentMonth'      => $currentDate->format("Y-m-d"),
                'checkins' => $listCheckins,
                'cleanings' => $listCleanings,
            ]);
        }
        
        $cronNotFinished = $this->getDoctrine()->getRepository('App\Entity\Cron')->findJobNotFinished();
        
        $tomorrowResaByCheckinDate = $this->get('App\Domain\ReportingOp')->getReportingResaByCheckinDateForPeriod($currentDate, $currentDate, $userContext);
        $tomorrowResaByCheckoutDate = $this->get('App\Domain\ReportingOp')->getReportingResaByCheckoutDateForPeriod($currentDate, $currentDate, $userContext);
        $tomorrowCleanings = $this->get('App\Domain\ReportingOp')->getReportingCleaningForPeriod($currentDate, $currentDate, $userContext);
        $tomorrowExtraCharges = $this->get('App\Domain\ReportingOp')->getReportingExtraChargeForPeriod($currentDate, $currentDate, $userContext);
        $tomorrowCheckins = $this->get('App\Domain\ReportingOp')->getReportingCheckinsForPeriod($currentDate, $currentDate, $userContext);
        
        // Get number of active reservations per market 
        $startDate = new \DateTime($currentDate->format("Y-m")."-01");
        $endDate = (clone $startDate)->add(new \DateInterval('P1M'))->sub(new \DateInterval('P1D'));
        $nbReservation = $this->get('App\Domain\ReportingOp')->getManagedReservationPerMarket($startDate, $endDate, $userContext);

        // Alerts for global warnings or inconsistent data 
        $warnings = $this->get('App\Domain\ReportingOp')->getWarningsAboutOperational($userContext);
        if (count($cronNotFinished) > 0)
            $warnings["error"]["cron"] = 1;
        
        return $this->render('dashboard.html.twig', [
            'debug'             => null,
            'alerts'            => $warnings,
            'markets'           => $markets,
            'selectedMarket'    => ($marketId == '' ? 12 : $marketId),
            'nbReservation'     => $nbReservation,
            'previousDate'      => (clone $currentDate)->sub(new \DateInterval('P1D'))->format('Y-m-d'),
            'nextDate'          => (clone $currentDate)->add(new \DateInterval('P1D'))->format('Y-m-d'),
            'currentMonth'      => $currentDate->format("Y-m-d"),
            'reporting'         => array("checkins" => $tomorrowCheckins, "resaByCheckinDate" => $tomorrowResaByCheckinDate, "resaByCheckoutDate" => $tomorrowResaByCheckoutDate, "cleanings" => $tomorrowCleanings, "extracharges" => $tomorrowExtraCharges),
        ]);
    }

    /**
     * @Route("/invoices", name="invoicing")
     * @Security("has_role('ROLE_INVOICES')")
     */
    public function invoicingAction()
    {
        $userContext = $this->get('session')->get('userContext', null);
        $em = $this->getDoctrine()->getManager();

        $invoicesGroups = $em->getRepository('App\Entity\Invoice')->findAllGroups($userContext);
        $monthsWithReservationsByYear = $em->getRepository('App\Entity\Reservation')->findAllMonthsWithReservationsByYear();
        
        // Add the number of managed reservations per market and period
        foreach ($invoicesGroups as $key => &$value)
        {
            $startDate = $value["billingDate"];
            $endDate = (clone $startDate)->add(new \DateInterval('P1M'))->sub(new \DateInterval('P1D'));
            
            $total = 0;
            foreach ($value["markets"] as &$market)
            {   
                $marketId = $market["marketId"];
                $managedReservations = $em->getRepository('App\Entity\Reservation')->findManagedByPeriodAndMarket($startDate, $endDate, $marketId, $userContext);
                $market["nbResa"] = count($managedReservations);
                $total += count($managedReservations);
            }
            
            $value["nbResa"] = $total;
        }
  
        return $this->render('invoicing.html.twig', [
            'invoicesGroups' => $invoicesGroups,
            'monthsWithReservationsByYear' => $monthsWithReservationsByYear,
       ]);
    }

    /**
     * @Route("/invoices/generate", name="generate_invoices")
     * @Security("has_role('ROLE_INVOICES')")
     */
    public function generateInvoicesAction(Request $request)
    {
        $year = $request->get('year');
        $month = $request->get('month');
        $invoiceManager = $this->get('App\Manager\InvoiceManager');
        try {
            $invoiceManager->generateInvoices(intval($year), intval($month));
        } catch(\Exception $e) {
            $request->getSession()->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirectToRoute('invoicing');
    }

    /**
     * @Route("/invoices/export/{yearMonth}/{marketId}/{invoiceRef}/{action}", name="export_invoices")
     * @Security("has_role('ROLE_INVOICES')")
     */
    public function exportInvoicesAction(string $yearMonth, int $marketId = -1, string $invoiceRef = "", bool $action = true)
    {
        $userContext = $this->get('session')->get('userContext');
        $em = $this->getDoctrine()->getManager();

        if ($invoiceRef != "")
        {
            // Mark this invoice as imported or reset its state
            $invoiceManager = $this->get('App\Manager\InvoiceManager');
            $invoiceManager->setInvoiceStatus($invoiceRef, $action);
        }
        
        $invoices = $em->getRepository('App\Entity\Invoice')->getInvoiceByDateAndMarket($yearMonth.'-01', $marketId, $userContext);
        return $this->render('invoices.html.twig', [
            'invoices' => $invoices
        ]);
    }

    /**
     * @Route("/reporting/financial/{yearMonth}", name="financial_reporting")
     * @Security("has_role('ROLE_REPORTING')")
     */
    public function financialReportingAction($yearMonth = null)
    {
        if(null == $yearMonth) {
            $yearMonth = (new \DateTime)->format('Y-m');
        }
        $dateStart = \DateTime::createFromFormat('Y-m-d', $yearMonth.'-01');
        $dateEnd = (clone $dateStart)->add(new \DateInterval('P1M'))->sub(new \DateInterval('P1D'));
        
        $reporting = $this->get('App\Domain\ReportingByMarket')->getReportingByMarketForPeriod($dateStart, $dateEnd);
        
        return $this->render('reportings/financial.html.twig', [
            'currentMonth' => $yearMonth,
            'nextMonth' => (clone $dateStart)->add(new \DateInterval('P1M'))->format('Y-m'),
            'previousMonth' => (clone $dateStart)->sub(new \DateInterval('P1M'))->format('Y-m'),
            'reporting' => $reporting
        ]);
    }
    
    /**
     * @Route("/reporting/charts/{year}", name="charts_reporting")
     * @Security("has_role('ROLE_REPORTING')")
     */
    public function chartsReportingAction(Request $request, $year = null)
    {   
        if ($year == null)
            $year = date("Y");
        
        // Get filters
        $filterMarketsIds = null;
        if ($request->isMethod('post'))
        {
            $filterMarketsIds = $request->get('filterMarkets');
        }
        
        $charts = $this->get('App\Domain\ReportingByMarket')->createColumnChartByMarketPerYear($year, $filterMarketsIds);
        
        $markets =  $this->getDoctrine()->getRepository('App\Entity\Market')->findAllWithFilter(null);

        return $this->render('reportings/charts.html.twig', [
            'markets' => $markets, 
            'charts' => $charts,
        ]);
    }
 
    /**
     * @Route("/reporting/checkins/{yearMonth}", name="checkins_reporting")
     * @Security("has_role('ROLE_REPORTING')")
     */
    public function checkinsReportingAction(Request $request, $yearMonth = null)
    {   
        if(null == $yearMonth) {
            $yearMonth = (new \DateTime)->format('Y-m');
        }
        $dateStart = \DateTime::createFromFormat('Y-m-d', $yearMonth.'-01');
        $dateEnd = (clone $dateStart)->add(new \DateInterval('P1M'))->sub(new \DateInterval('P1D'));
        
        $reporting = $this->get('App\Domain\ReportingByMarket')->getCheckinReportingByMarketForPeriod($yearMonth);
        
        return $this->render('reportings/checkins.html.twig', [
            'currentMonth' => $yearMonth,
            'nextMonth' => (clone $dateStart)->add(new \DateInterval('P1M'))->format('Y-m'),
            'previousMonth' => (clone $dateStart)->sub(new \DateInterval('P1M'))->format('Y-m'),
            'reporting' => $reporting
        ]);
    }
    
    /**
     * @Route("/reporting/checkins/{yearMonth}/{checkerId}", name="checkins_detail_reporting")
     * @Security("has_role('ROLE_REPORTING')")
     */
    public function checkinDetailReportingAction(string $yearMonth, int $checkerId = -1)
    {
        $userContext = $this->get('session')->get('userContext');
        $em = $this->getDoctrine()->getManager();
        
        $dateStart = \DateTime::createFromFormat('Y-m-d', $yearMonth.'-01');
        $dateEnd = (clone $dateStart)->add(new \DateInterval('P1M'))->sub(new \DateInterval('P1D'));
        
        $checker = $em->getRepository('App\Entity\Checker')->findOneByWIthFilter(['id' => $checkerId], $userContext);
        $checkins = $em->getRepository('App\Entity\Checkin')->findByCheckerAndPeriod($dateStart, $dateEnd, $checker, $userContext);
        return $this->render('reportings/checkinsDetail.html.twig', [
            'yearMonth' => $yearMonth,
            'checkins' => $checkins
        ]);
    }

    /**
     * @Route("/exports", name="exports")
     * @Security("has_role('ROLE_REPORTING')")
     */
    public function exportsAction(Request $request)
    {
        if($request->isMethod('post')) {

            if($request->request->get('export_reservations')) {
                $response = $this->get('App\Domain\Exports')->streamed('exportReservations');
                // To DEBUG in a browser, uncomment the next line
                // $response->sendContent(); die;
                return $response;

            }
        }

        return $this->render('exports.html.twig', []);
    }

    /**
     * @Route("/properties", name="view_properties")
     * @Security("has_role('ROLE_VIEW_PROPERTY')")
     */
    public function viewPropertiesAction(Request $request)
    {
        $hostId = $request->get('id');
        return $this->redirect($this->generateUrl('easyadmin', [
            'action' => 'list',
            'entity' => 'Property',
            'byHost' => $hostId
        ]));
    }

    /**
     * @Route("/reservations", name="view_reservations")
     * @Security("has_role('ROLE_VIEW_RESERVATION')")
     */
    public function viewReservationsAction(Request $request)
    {
        $propertyId = $request->get('id');
        return $this->redirect($this->generateUrl('easyadmin', [
            'action' => 'list',
            'entity' => 'Reservation',
            'byProperty' => $propertyId
        ]));
    }

    /**
     * @Route("/import/{city}", name="import")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function importCityAction(string $city)
    {
        $userContext = $this->get('session')->get('userContext');
        $em = $this->getDoctrine()->getManager();
        
        /*$em->getConnection()
          ->getConfiguration()
          ->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger())
        ;*/

        $market = $em->getRepository('App\Entity\Market')->findOneByWithFilter(['name' => $city], $userContext);

        if ($market != null)
        {
            print_r("Found ID = ".$market->getId());
            $obj = $this->get('App\DataFixtures\ORM\Fixtures');
            $obj->addCity($em, $market, strtolower($market->getName()));
        }
        
        
        return new Response(
            '<html><body>'.($market==null ? "Not found !" : "Imported").'</body></html>'
        );
    }
    
    /**
     * @Route(path = "/ajaxGetResource", name = "ajax_get_resource")
     * @Security("has_role('ROLE_USER')")
     */
    public function ajaxGetResource(Request $request)
    {
        $userContext = $this->get('session')->get('userContext');
        $em = $this->getDoctrine()->getManager();
        if ($request->isXMLHttpRequest())
        {   
            $properties = $em->getRepository('App\Entity\Property')->findAllWithFilter($userContext);
            
            // Sort results
            usort($properties, function($a, $b) { 
                    // Sort by market first
                    if ($a->getMarket()->getName() !== $b->getMarket()->getName())
                        return $a->getMarket()->getName() > $b->getMarket()->getName(); 
                    
                    // Sort by property name
                    return $a->getLabel() > $b->getLabel(); 
                }
            );
            
            $response = [];
            foreach ($properties as $property)
            {
                // Get only managed property
                if ($property->isCurrentlyManaged())
                {                  
                    $response[] = ["id" => $property->getId(), "title" => $property->getLabel(), "market" => $property->getMarket()->getName()];
                }
            }
            return new JsonResponse($response);
        }
    }
}
