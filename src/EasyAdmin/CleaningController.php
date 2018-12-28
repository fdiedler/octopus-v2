<?php
namespace App\EasyAdmin;

use App\Entity\Property;
use App\Entity\Reservation;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; 
use App\Entity\Cleaning;

use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder; 


/**
 * http://symfony.com/doc/current/bundles/EasyAdminBundle/book/complex-dynamic-backends.html#customization-based-on-overriding-the-default-admincontroller
 */
class CleaningController extends BaseAdminController
{
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
    
    public function createNewEntity()
    {
        // Get current user context
        $userContext = $this->get('session')->get('userContext');
        
        // Create a new instance of Cleaning
        $entityFullyQualifiedClassName = $this->entity['class'];
        $entity = new $entityFullyQualifiedClassName();
        
        // Get the right reservation entity
        $reservationId = $this->request->get('reservation');
        $reservationEntity = $this->getDoctrine()->getRepository('App\Entity\Reservation')->findByWithFilter(["id" => $reservationId], $userContext);
        if (count($reservationEntity) == 1)
        {
            // Associate this cleaning to an existing property AND reservation
            $entity->setReservation($reservationEntity[0]);
            $entity->setProperty($reservationEntity[0]->getProperty());
        }
        else
        {
            $propertyId = $this->request->get('property');
            $propertyEntity = $this->getDoctrine()->getRepository('App\Entity\Property')->findByWithFilter(["id" => $propertyId], $userContext);
            if (count($propertyEntity) == 1)
            {
                // Associate this cleaning to an existing property WITHOUT reservation
                $entity->setProperty($propertyEntity[0]);
            }
        }
        
        return $entity;
    }
  
    public function createEntityFormBuilder($entity, $view)
    {
        $userContext = $this->get('session')->get('userContext');
        
        $reservationId = $this->request->get('reservation', -1);
            
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
   
        // Retrieve all cleaning types
        $cleaningTypes = array("" => "");
        foreach (Cleaning::CLEANING_DATA as $cleaning)
        {
            $cleaningTypes[$cleaning["label"]] = $cleaning["type"];
        }
        
        // Override the field for cleaning type
        $formBuilder->add('type', ChoiceType::class, array(
            'choices' => $cleaningTypes)
        );
        
        if ($reservationId == -1 && $entity->getReservation() == null)
        {
            // This cleaning is only linked to a property (not a reservation)
            // Hide reservation field to prevent data corruptions...
            $formBuilder->add('reservation', HiddenType::class, array(
                'disabled' => true)
            );
            
            $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::PROPERTY_FIELD);
        }
        else
        {
            // This cleaning is linked to a property AND a reservation
            // Disabled property and reservation fields to prevent data corruptions...
            $formBuilder->get('property')->setDisabled(true);
            $formBuilder->get('reservation')->setDisabled(true);
        }
        
        // Add a field to select cleaning presta according to the property market
        $formBuilder->add('presta', EntityType::class, [ 
          'class' => 'App:CleaningPresta', 
          'label' => 'Assigned to',
          'required' => false,
          'query_builder' => function (EntityRepository $er) use ($entity, $userContext) { 
                   if ($entity->getProperty() === null)
                   {
                       // Use the user context instead
                       $queryBuilder = $er->createQueryBuilder('cp');
                       $queryBuilder = $er->addFilterConstraints($queryBuilder, $userContext);
                       return $queryBuilder;
                   }
                   
                   $queryBuilder = $er->createQueryBuilder('cp')
                        ->andWhere('cp.market = :propertyMarket')
                        ->setParameter('propertyMarket', $entity->getProperty()->getMarket())
                   ;
                   
                   return $queryBuilder;
              }, 
        ]);
        
        if ($view == "new")
        {
            if ($entity->getReservation() != null)
            {
                // Set by default the checkout date
                $formBuilder->add('date', DateType::class, array(
                     'widget' => 'single_text',
                     'required' => true,
                     'data' => $entity->getReservation()->getCheckoutDate(),
                ));
            }
            else
            {
                $params = $this->request->get('redirectParam',"ee");
                
                // Set to the current dashboard date because this cleaning is not linked to a reservation
                $formBuilder->add('date', DateType::class, array(
                     'widget' => 'single_text',
                     'required' => true,
                     'data' => (isset($params["date"]) ? new \DateTime($params["date"]) : new \DateTime("now")),
                ));
            }
        }
  
        return $formBuilder;
    }
    
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        $em = $this->getDoctrine()->getManagerForClass($this->entity['class']);
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($this->entity['class'], 'entity')
            ->join('entity.property', 'property')
            ->orWhere('LOWER(property.label) LIKE :query')
            ->setParameter('query', '%'.strtolower($searchQuery).'%')
        ;
        
        return $queryBuilder;
    }
}
