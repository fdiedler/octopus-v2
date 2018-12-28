<?php

namespace App\EasyAdmin;

use App\Entity\User;
use App\Entity\Property;
use App\Entity\CleaningPresta;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder; 
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;

class PropertyController extends BaseAdminController
{
    private function computeNbNightsPerProperty($paginator)
    {
        $userContext = $this->get('session')->get('userContext');
        $currentYear = date("Y");
        
        // Compute number of nights booked for eahc property
        $start = new \DateTime("$currentYear-01-01");
        $end = new \DateTime("$currentYear-12-31");
        foreach ($paginator->getCurrentPageResults() as $record)
        {    
            $nbNights = $this->em->getRepository('App\Entity\Reservation')->getNumberOfNightsForOneProperty($record, $start, $end, $userContext);
            $record->nbNights = $nbNights;
        }
    }
    
    protected function listAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_LIST);

        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $this->dispatch(EasyAdminEvents::POST_LIST, array('paginator' => $paginator));
  
        // Compute number of nights booked for each property
        $this->computeNbNightsPerProperty($paginator);
        
        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }
    
     protected function searchAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_SEARCH);

        $searchableFields = $this->entity['search']['fields'];
        $paginator = $this->findBy($this->entity['class'], $this->request->query->get('query'), $searchableFields, $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['search']['dql_filter']);
        $fields = $this->entity['list']['fields'];

        $this->dispatch(EasyAdminEvents::POST_SEARCH, array(
            'fields' => $fields,
            'paginator' => $paginator,
        ));

        // Compute number of nights booked for each property
        $this->computeNbNightsPerProperty($paginator);
        
        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }
    
    // Creates the Doctrine query builder used to get all the items. Override it
    // to filter the elements displayed in the listing
    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        if($this->request->get('byHost') != null) {
            $hostId = (int) $this->request->get('byHost');
            $dqlFilter = "entity.host = $hostId";
        }
        
        return parent::createListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter);
    }
    
    public function createEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        $userContext = $this->get('session')->get('userContext');
        
        $oldHostProperty = $formBuilder->get('host');
        $oldMarketProperty = $formBuilder->get('market');
        $oldDefaultCleaningPrestaProperty = $formBuilder->get('defaultCleaningPresta');
        $oldTypeProperty = $formBuilder->get('type');
        $oldAccessFormUrlProperty = $formBuilder->get('accessFormUrl');
        
        // Retrieve all types
        $types = array("" => "");
        foreach (Property::TYPE_DATA as $type)
        {
            $types[$type["label"]] = $type["type"];
        }
        
        // Override the field for type
        $formBuilder->add('type', ChoiceType::class, array(
            'label' => 'Type of contract',
            'choices' => $types)
        );
        
        $formBuilder->add('host', EntityType::class, [ 
          'class' => 'App:Host', 
          'query_builder' => function (EntityRepository $er) use ($userContext) { 
                   $queryBuilder = $er->createQueryBuilder('u');
                   $queryBuilder = $er->addFilterConstraints($queryBuilder, $userContext);
                   return $queryBuilder->orderBy('u.name', 'ASC');
              }, 
        ]); 
        
        $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::MARKET_FIELD);
        
        $formBuilder->add('defaultCleaningPresta', EntityType::class, [ 
          'class' => 'App:CleaningPresta', 
          'required' => false,
          'query_builder' => function (EntityRepository $er) use ($userContext) { 
                   $queryBuilder = $er->createQueryBuilder('cp');
                   $queryBuilder = $er->addFilterConstraints($queryBuilder, $userContext);
                   return $queryBuilder;
              }, 
        ]);
        
        /***** Hack - Should be improved later... ****/
        // Browse access form file for the current market
        $files = [];
        $marketId = $entity->getMarket() ? $entity->getMarket()->getId() : null;
        if ($marketId != null)
        {
            $path  = $this->container->get('kernel')->getRootdir()."/../public/downloads/forms/$marketId/";
            $allFiles = @scandir($path);
            if ($allFiles !== false)
            {
                foreach ($allFiles as $f) 
                {
                    if ($f !== '.' and $f !== '..')
                    {
                        $files[$f] = $this->request->getSchemeAndHttpHost()."/downloads/forms/$marketId/".$f;
                    }
                }
            }
        }
        /**********************************************/
        
        // Override the field for accessFormUrl
        $formBuilder->add('accessFormUrl', ChoiceType::class, array(
            'label' => 'Access form file',
            'required' => false,
            'choices' => $files)
        );
        
        // Restore right layout from easyadmin config
        $formBuilder->get('host')->setAttribute('easyadmin_form_tab', $oldHostProperty->getAttribute('easyadmin_form_tab'));
        $formBuilder->get('host')->setAttribute('easyadmin_form_group', $oldHostProperty->getAttribute('easyadmin_form_group'));
        $formBuilder->get('market')->setAttribute('easyadmin_form_tab', $oldMarketProperty->getAttribute('easyadmin_form_tab'));
        $formBuilder->get('market')->setAttribute('easyadmin_form_group', $oldMarketProperty->getAttribute('easyadmin_form_group'));
        $formBuilder->get('defaultCleaningPresta')->setAttribute('easyadmin_form_tab', $oldDefaultCleaningPrestaProperty->getAttribute('easyadmin_form_tab'));
        $formBuilder->get('defaultCleaningPresta')->setAttribute('easyadmin_form_group', $oldDefaultCleaningPrestaProperty->getAttribute('easyadmin_form_group'));
        $formBuilder->get('type')->setAttribute('easyadmin_form_tab', $oldTypeProperty->getAttribute('easyadmin_form_tab'));
        $formBuilder->get('type')->setAttribute('easyadmin_form_group', $oldTypeProperty->getAttribute('easyadmin_form_group'));
        $formBuilder->get('accessFormUrl')->setAttribute('easyadmin_form_tab', $oldAccessFormUrlProperty->getAttribute('easyadmin_form_tab'));
        $formBuilder->get('accessFormUrl')->setAttribute('easyadmin_form_group', $oldAccessFormUrlProperty->getAttribute('easyadmin_form_group'));
        
        return $formBuilder;
    }
}