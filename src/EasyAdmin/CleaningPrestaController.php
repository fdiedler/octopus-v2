<?php

namespace App\EasyAdmin;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder; 

class CleaningPrestaController extends BaseAdminController
{   
    public function createEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        $userContext = $this->get('session')->get('userContext');
        
        $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::MARKET_FIELD);
        $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::USER_FIELD);

        return $formBuilder;
    }
}