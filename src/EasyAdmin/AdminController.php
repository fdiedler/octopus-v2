<?php

namespace App\EasyAdmin;

use App\Entity\Property;
use App\Entity\Reservation;
use App\Entity\MarketObjective;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Cleaning;
use App\Security\UserContext;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder; 

/**
 * http://symfony.com/doc/current/bundles/EasyAdminBundle/book/complex-dynamic-backends.html#customization-based-on-overriding-the-default-admincontroller
 */
class AdminController extends BaseAdminController
{
    // Used by applyFilterFor() function
    const PROPERTY_FIELD = "property";
    const MARKET_FIELD = "market";
    const USER_FIELD = "user";
    
    var $request;
    
    protected function initialize(Request $request)
    {
        $this->request = $request;
        return parent::initialize($request);
    }
    
    // Apply filter on a field according to the user context
    public static function applyFilterFor($formBuilder, $userContext, $field)
    {
        if ($field == self::PROPERTY_FIELD)
        {
            $formBuilder->add('property', EntityType::class, [ 
                'class' => 'App:Property', 
                'query_builder' => function (EntityRepository $er) use ($userContext) { 
                       $queryBuilder = $er->createQueryBuilder('p');
                       $queryBuilder = $er->addFilterConstraints($queryBuilder, $userContext);
                       return $queryBuilder->orderBy('p.label', 'ASC');
                  }, 
            ]);
        }
        else if ($field == self::MARKET_FIELD)
        {
            $formBuilder->add('market', EntityType::class, [ 
              'class' => 'App:Market', 
              'query_builder' => function (EntityRepository $er) use ($userContext) { 
                       $queryBuilder = $er->createQueryBuilder('m');
                       $queryBuilder = $er->addFilterConstraints($queryBuilder, $userContext);
                       return $queryBuilder;
                  }, 
            ]);
        }
        else if ($field == self::USER_FIELD)
        {
            $formBuilder->add('user', EntityType::class, [ 
              'class' => 'App:User', 
              'label' => 'User account',
              'required' => false,
              'query_builder' => function (EntityRepository $er) use ($userContext) { 
                       $queryBuilder = $er->createQueryBuilder('u');
                       if (!$userContext->isSuperAdmin())
                       {
                           $queryBuilder
                                ->andWhere('u.allowedMarketId = :allowedMarketId')
                                ->setParameter('allowedMarketId', serialize($userContext->getAllowedMarketId()))
                           ;
                       }
                       
                       return $queryBuilder;
                  }, 
            ]);
        }
        
        
        return $formBuilder;
    }

    protected function redirectToReferrer()
    {
        // Just perform special redirections
        $redirect = $this->request->get('redirect', '');
        if (!empty($redirect))
        {
            $redirectParam = $this->request->get('redirectParam', '');
            if (!empty($redirectParam))
                return $this->redirect($this->generateUrl($redirect, $redirectParam));
            return $this->redirect($this->generateUrl($redirect));
        }
        
        $referrerUrl = $this->request->query->get('referer', '');

        return !empty($referrerUrl)
            ? $this->redirect(urldecode($referrerUrl))
            : $this->redirect($this->generateUrl('easyadmin', array(
                'action' => 'list', 'entity' => $this->entity['name'],
            )));
    }
    
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if(Reservation::class == $entityClass)
        {
            /* @var EntityManager */
            $em = $this->getDoctrine()->getManagerForClass($this->entity['class']);
            
            /* @var DoctrineQueryBuilder */
            $queryBuilder = $em->createQueryBuilder()
                ->select('entity')
                ->from($this->entity['class'], 'entity')
                ->join('entity.property', 'property')
                ->orWhere('LOWER(property.label) LIKE :query')
                ->setParameter('query', '%'.strtolower($searchQuery).'%')
            ;
            
            if (!empty($dqlFilter)) {
                $queryBuilder->andWhere($dqlFilter);
            }
            
            if (null !== $sortField) {
                $queryBuilder->orderBy('entity.'.$sortField, $sortDirection ?: 'DESC');
            }
            
            return $queryBuilder;
        }
        
        // Récupération du query builder parent
        $qb = parent::createSearchQueryBuilder($entityClass, $searchQuery, $searchableFields, $sortField, $sortDirection, $dqlFilter);
        return $qb;
    }

    // Creates the Doctrine query builder used to get all the items. Override it
    // to filter the elements displayed in the listing
    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        if(Reservation::class == $entityClass && $this->request->get('byProperty') != null) {
            $propertyId = (int) $this->request->get('byProperty');
            $dqlFilter = "entity.property = $propertyId";
        }
        
        return parent::createListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter);
    }

    /**
     * @Route(path = "/getCheckinPrice", name = "get_checkin_price")
     * @Security("has_role('ROLE_USER')")
     */
    public function ajaxGetCheckinPrice(Request $request)
    {
        $userContext = $request->getSession()->get('userContext');
        
        if ($request->isXMLHttpRequest())
        {
            $checkerId = $request->get('checkerId');
            $checkerData =  $this->getDoctrine()->getRepository('App\Entity\Checker')->findOneByWithFilter(["id" => $checkerId], $userContext);
            $response = array("checkinPrice" => $checkerData->getAmountWithoutTaxes());
            return new JsonResponse($response);
        }
        
        die("ajaxGetCheckinPrice");
    }
    
    /**
     * @Route(path = "/getCleaningPrice", name = "get_cleaning_price")
     * @Security("has_role('ROLE_USER')")
     */
    public function ajaxGetCleaningPrice(Request $request)
    {
        $userContext = $request->getSession()->get('userContext');
        
        if ($request->isXMLHttpRequest())
        {
            $propertyId = (int)$request->get('propertyId');
            $cleaningType = (string)$request->get('cleaningType');
            
            // Get all cleaning prices for a given property
            $propertyData =  $this->getDoctrine()->getRepository('App\Entity\Property')->findByWithFilter(["id" => $propertyId], $userContext);
            
            $response = array("cleaningPrice" => "0", "propertyId" => $propertyId);
            if ($cleaningType != "" && count($propertyData) == 1)
            {
                $propertyData = $propertyData[0];
                
                // Get the right function to call according to cleaning type
                $func = "";
                foreach (Cleaning::CLEANING_DATA as $cleaning)
                {
                    if ($cleaning["type"] == $cleaningType)
                    {
                        $func = $cleaning["func"];
                        break;
                    }
                }
                
                // Get the associated price 
                if ($func != "")
                {
                    $response["cleaningPrice"] = $propertyData->$func();
                }
            }
            
            return new JsonResponse($response);
        }
        
        die("ajaxGetCleaningPrice");
    }
    
    /**
     * @Route(path = "/addCleaning", name = "cleaning_add")
     * @Security("has_role('ROLE_USER')")
     */
    public function addAction(Request $request)
    {
        $reservationId = $request->get('id');
        return $this->redirect($this->generateUrl('easyadmin', [
            'action' => 'new',
            'entity' => 'Cleaning',
            'reservation' => $reservationId,
            'redirect' => 'admin',
            'redirectParam' => ['entity' => 'ImportPrice'],
        ]));
        
    }
    
    /**
     * @Route(path = "/changeStatus", name = "change_status")
     * @Security("has_role('ROLE_USER')")
     */
    public function changeReservationStatusAction(Request $request)
    {
        $userContext = $request->getSession()->get('userContext');
        $reservationId = $request->get('id');
        $status = $request->get('status', '');
        $em = $this->getDoctrine()->getManagerForClass('App\Entity\Reservation');
        $reservation = $em->getRepository('App\Entity\Reservation')->findByWithFilter(["id" => $reservationId], $userContext);
        if (count($reservation) == 1)
        {
            if ($status == "")
            {
                // Canceled this reservation
                $reservation[0]->setStatus(Reservation::STATUS_CANCELED);
            }
            else
            {
                // Restore this reservation
                $reservation[0]->setStatus(Reservation::STATUS_CONFIRMED);
            }
            $em->flush($reservation[0]);
        }
        
        // Redirect to referer in order to preserve filters on list view
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route(path = "/addCleaningProperty", name = "cleaning_add_property")
     * @Security("has_role('ROLE_USER')")
     */
    public function addCleaningPropertyAction(Request $request)
    {
        $propertyId = $request->get('id');
        return $this->redirect($this->generateUrl('easyadmin', [
            'action' => 'new',
            'entity' => 'Cleaning',
            'property' => $propertyId,
            'redirect' => 'admin',
            'redirectParam' => ['entity' => 'Property'],
        ]));
        
    }
    
    private function simulateUserLogging($userId)
    {
        $user = $this->getDoctrine()->getRepository('App\Entity\User')->findOneBy(["id" => $userId]);
        if ($user)
        {
            // Simulate logging with the given user
            $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                $user,
                null,
                'main',
                $user->getRoles()
            );
            
            // Update session because filters are stored here
            $userContext = new UserContext($user->getAllowedMarketId(), $user->getEmail());
            $this->get('session')->set('userContext', $userContext);
            
            // Apply new token
            $this->container->get('security.token_storage')->setToken($token);
           
            return true;
        }
        
        return false;
    }
    
    /**
     * @Route(path = "/restoreUser", name = "restore_user")
     * @Security("has_role('ROLE_MIN')")
     */
    public function restoreUserAction(Request $request)
    {
        $userAdminId = $this->get('session')->get('adminUserId');        
        if (!$this->simulateUserLogging($userAdminId))
        {
            throw new \Exception("User #$userId not exist !");
        }
        
        // Remove session that are not required yet
        $this->get('session')->remove('adminUserId');

        return $this->redirect("/dashboard");
    }
    
    /**
     * @Route(path = "/marketObjectives", name = "market_objectives")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function MarketObjectivesAction(Request $request)
    {
        $marketId = $request->get('id');
        $userContext = $request->getSession()->get('userContext');
        $market = $this->getDoctrine()->getRepository('App\Entity\Market')->findOneByWithFilter(["id" => $marketId], $userContext);
        
        // Get objectives for this market if already set
        $objectives = [];
        foreach ($market->getObjectives() as $marketObjective)
        {
            $key = $marketObjective->getDate()->format('Y-m')."-01";
            $objectives[$key] = $marketObjective->getValue();
        }
        
        return $this->render('marketObjectives.html.twig', [
            "market" => $market,
            "objectives" => $objectives,
        ]);
    }
    
    /**
     * @Route("/marketObjectivesEdit", name="market_objectives_edit")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function MarketObjectivesEditAction(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $userContext = $request->getSession()->get('userContext');
            $marketId = $request->get("market_id");
            $market = $this->getDoctrine()->getRepository('App\Entity\Market')->findOneByWithFilter(["id" => $marketId], $userContext);
            $currentYear = date("Y");
            
            if ($market !== null)
            {
                // Get all post variables send by the form
                for ($i=1; $i<=12; $i++)
                {
                    $month = ($i < 10 ? "0".$i : $i);
                    $value = $request->get($currentYear."_".$month);
                    if ($value !== "")
                    {
                        $objectiveDate = new \DateTime($currentYear."-".$month."-01");
                     
                        // Check if this objective for this market and this date already exists
                        $marketObjective = $this->getDoctrine()->getRepository('App\Entity\MarketObjective')
                            ->findObjectiveByMarketAndDate($market, $objectiveDate, $userContext);
                        if (null === $marketObjective)
                        {
                            // Create new objective
                            $marketObjective = new MarketObjective();
                            $marketObjective->setMarket($market);
                            $marketObjective->setType(0);
                            $marketObjective->setDate($objectiveDate);
                            $marketObjective->setValue(intval($value));
                        }
                        else
                        {
                            // Update the existing objective
                            $marketObjective->setValue(intval($value));
                        }
                        
                        $this->getDoctrine()->getManager()->persist($marketObjective);
                    }
                }
                
                // Save modifications into database
                $this->getDoctrine()->getManager()->flush();
                        
                // Redirect to referer in order to preserve filters on list view
                return $this->redirect($request->headers->get('referer'));
            }
        }
        
        return $this->redirect("/dashboard");
    }
    
    /**
     * @Route(path = "/changeUserLogged", name = "change_user_logged")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function changeUserLoggedAction(Request $request)
    {
        $userId = $request->get('id');
        $currentLoggedUser = $this->get('security.token_storage')->getToken()->getUser();
        
        // Simulate a logging to the given user
        // NB: We will loose admin rights...
        if (!$this->simulateUserLogging($userId))
        {
            throw new \Exception("User #$userId not exist !");
        }

        // Store the admin user id in session
        $this->get('session')->set('adminUserId', $currentLoggedUser->getId());
        
        return $this->redirect("/dashboard");
    }
}
