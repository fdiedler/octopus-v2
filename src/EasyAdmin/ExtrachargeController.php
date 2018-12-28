<?php

namespace App\EasyAdmin;

use App\Entity\Property;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;


class ExtrachargeController extends BaseAdminController
{   
    public function createEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        $userContext = $this->get('session')->get('userContext');
        
        $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::PROPERTY_FIELD);
        
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