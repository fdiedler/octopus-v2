<?php

namespace App\EasyAdmin;

use App\Entity\User;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserController extends BaseAdminController
{
    private function separate($paginator)
    {
        // Get all checker / cleanings providers users based on its role
        $users = [];
        $checkers = [];
        $cleaningsProviders = [];
        foreach ($paginator->getCurrentPageResults() as $record)
        {
            if ($record->hasCheckerAccess())
                $checkers[] = $record;
            if ($record->hasCleaningAccess())
                $cleaningsProviders[] = $record;
            
            if (!$record->hasCheckerAccess() && !$record->hasCleaningAccess())            
                $users[] = $record;
            
            // Convert market ids to market names
            $marketIds = $record->getAllowedMarketId() ?? null;
            if ($marketIds)
            {
                $marketNames = array_map(function($marketId) { 
                        $marketName = $this->getDoctrine()->getRepository('App\Entity\Market')->findOneByWithFilter(['id' => $marketId], null);
                        return $marketName;
                    }, $marketIds);
                
                $record->setAllowedMarketId($marketNames);
            }
        }
        
        return ['users' => $users, 'checkers' => $checkers, 'cleaningsProviders' => $cleaningsProviders];
    }
    
    protected function listAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_LIST);

        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $tab = $this->separate($paginator);
        
        $this->dispatch(EasyAdminEvents::POST_LIST, array('paginator' => $paginator));
  
        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'checkers' => $tab['checkers'],
            'cleaningsProviders' => $tab['cleaningsProviders'],
            'users' => $tab['users'],
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }
    
    /**
     * The method that is executed when the user performs a query on an entity.
     *
     * @return Response
     */
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

        $tab = $this->separate($paginator);
        
        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'checkers' => $tab['checkers'],
            'cleaningsProviders' => $tab['cleaningsProviders'],
            'users' => $tab['users'],
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }
    
    public function createEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        // Passing no user context allow to get all available markets (no filters)
        $markets = $this->getDoctrine()->getRepository('App\Entity\Market')->findAllWithFilter(null);
        $marketsChoice = array();
        foreach ($markets as $market)
        {
            $marketsChoice[$market->getName()] = $market->getId();
        }
        
        // Add a field to select allowed market for this user
        $formBuilder->add('allowedMarketId', ChoiceType::class, array(
            'choices' => $marketsChoice,
            'multiple' => true,
            )
        );
        
        // Get available roles from security package
        $availableRoles = array_keys($this->getParameter('security.role_hierarchy.roles'));
        
        // Format roles and discard some internal roles
        foreach($availableRoles as $index => $value)
        {
            if ($value !== 'ROLE_MIN')
                $choiceRoles[$value] = $value;
        }
        
        // Override the easyadmin 'role' property
        $defaultValue = ($view == 'new' ? '' : implode($entity->getRoles()));
        $formBuilder->add('roles', ChoiceType::class, array(
            'choices' => $choiceRoles,
            'multiple' => false,
            'placeholder' => 'Choose a role...',
            'data' => $defaultValue
            )
        );
        
        return $formBuilder;
    }
}