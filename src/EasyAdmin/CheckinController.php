<?php
namespace App\EasyAdmin;

use App\Entity\Property;
use App\Entity\Reservation;
use App\Entity\Checkin;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType; 
use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder;

/**
 * http://symfony.com/doc/current/bundles/EasyAdminBundle/book/complex-dynamic-backends.html#customization-based-on-overriding-the-default-admincontroller
 */
class CheckinController extends BaseAdminController
{    
    private function sendCheckerMail(Checkin $checkin)
    {
        if ($checkin->getChecker()->getUser() == null)
            return false;
        
        // Get current logged user
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        
        $parameters = [
            'checker_name' => $checkin->getChecker()->getName(),
            'checkin_date' => $checkin->getDate(),
            'checkin_hour' => $checkin->getTime()->format('H:i'),
            'checkin_property' => $checkin->getProperty()->getLabel(),
        ];
        
        $to = $checkin->getChecker()->getUser()->getEmail();
        $this->get('App\Manager\MailManager')->sendEmail('checker', $parameters, $to);
        
        return true;
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
    
    public function newAction()
    {
        $response = parent::newAction();
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];
        
        // Send mail here
        if ($this->request->isMethod('post') && $entity->getChecker())
        {
            $this->sendCheckerMail($entity);
        }
        
        return $response;
    }
    
    public function editAction()
    {
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];
        $oldChecker = $entity->getChecker();
        $response = parent::editAction();
        
        // Send email if we change the Checker
        if ($this->request->isMethod('post') 
            && $oldChecker && $entity->getChecker()
            && $oldChecker->getId() != $entity->getChecker()->getId()
          )
        {
            $this->sendCheckerMail($entity);
        }
        
        return $response;
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
        $reservationEntity = $this->getDoctrine()->getRepository('App\Entity\Reservation')->findOneByWithFilter(["id" => $reservationId], $userContext);
        if ($reservationEntity)
        {
            // Associate this cleaning to an existing property AND reservation
            $entity->setReservation($reservationEntity);
        }
        else
        {
            throw new \Exception("Reservation ID does not exist !");
        }
        
        return $entity;
    }
    
    public function createEntityFormBuilder($entity, $view)
    {
        $userContext = $this->get('session')->get('userContext');
        
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        // Add a field to select checker according to the property market
        $formBuilder->add('checker', EntityType::class, [ 
          'class' => 'App:Checker', 
          'label' => 'Assigned to',
          'required' => false,
          'query_builder' => function (EntityRepository $er) use ($entity, $userContext) { 
                   $queryBuilder = $er->createQueryBuilder('ch')
                        ->andWhere('ch.market = :propertyMarket')
                        ->setParameter('propertyMarket', $entity->getProperty()->getMarket())
                   ;
                   
                   return $queryBuilder;
              }, 
        ]);
        
        if ($view == "new")
        {
            // Set by default the checkin date
            $formBuilder->add('date', DateType::class, array(
                 'widget' => 'single_text',
                 'required' => true,
                 'data' => $entity->getReservation()->getCheckinDate(),
            ));
        }
        
        return $formBuilder;
    }
    
}
