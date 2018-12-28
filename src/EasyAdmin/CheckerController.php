<?php

namespace App\EasyAdmin;

use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder; 

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\FormError;

use App\Entity\User;

class CheckerController extends BaseAdminController
{   
    public function newAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_NEW);

        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');

        $easyadmin = $this->request->attributes->get('easyadmin');
        $easyadmin['item'] = $entity;
        $this->request->attributes->set('easyadmin', $easyadmin);

        $fields = $this->entity['new']['fields'];

        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', array($entity, $fields));

        $newForm->handleRequest($this->request);
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            // Create Checker Account
            
            $ok = true;
            $user = $this->em->getRepository('App\Entity\User')->findOneBy(['email' => $entity->getVirtualUserLogin()]);
            if (!$user)
            {
                // Create a new User with a checker role
                $user = new User();
                $user->setEmail($entity->getVirtualUserLogin());
                $user->setRoles('ROLE_CHECKER');
                $user->setAllowedMarketId([$entity->getMarket()->getId()]);
                $this->em->persist($user);
                $this->em->flush();
            }
            else if ($user->hasCleaningAccess())
            {
                // Add checker role 
                $user->setRoles('ROLE_CLEANING_CHECKER');
                $this->em->persist($user);
                $this->em->flush();
            }
            else
            {
                // Duplicate not allowed !
                $newForm->get('virtualUserLogin')->addError(new FormError('This Gmail address is already registered !'));
                $ok = false;
            }
            
            if ($ok)
            {
                // Update current entity to link the user account created above with this Checker
                $entity->setUser($user);
                
                $this->dispatch(EasyAdminEvents::PRE_PERSIST, array('entity' => $entity));

                $this->executeDynamicMethod('prePersist<EntityName>Entity', array($entity));

                $this->em->persist($entity);
                $this->em->flush();
                
                $this->dispatch(EasyAdminEvents::POST_PERSIST, array('entity' => $entity));

                return $this->redirectToReferrer();
            }
        }

        $this->dispatch(EasyAdminEvents::POST_NEW, array(
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ));

        return $this->render($this->entity['templates']['new'], array(
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ));
    }
    
    public function createEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        $userContext = $this->get('session')->get('userContext');
        
        $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::MARKET_FIELD);
        
        if ($view == 'new')
        {
            // Allow only Gmail/wehost address
            $formBuilder->add('virtualUserLogin', EmailType::class, array('attr' => array('placeholder' => 'Gmail or Wehost address'),
                'label' => 'Checker email',
                'constraints' => array(
                    new Regex(array('pattern' => "/^[a-zA-Z0-9_.-]+@(gmail.com|wehost.fr)/", "message" => "Not a valid Gmail / Wehost address")),
                )
            ));
        }
        else
        {
            // Only admins can modify the user account associated to this Checker
            if ($userContext->isSuperAdmin())
                $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::USER_FIELD);
            else $formBuilder->remove('user');
        }
        
        return $formBuilder;
    }
}