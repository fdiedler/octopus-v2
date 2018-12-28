<?php

namespace App\EasyAdmin;

use App\Entity\Reservation;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository; 
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class ImportPriceController extends BaseAdminController
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

        $userContext = $this->get('session')->get('userContext');
        $markets = $this->em->getRepository('App\Entity\Market')->findAllWithFilter($userContext);
        
        // Hack to count the number of managed reservation *******
        $oldMaxPerPage = $paginator->getMaxPerPage();
        $paginator->setMaxPerPage(10000);
        $nbManaged = 0;
        foreach ($paginator->getCurrentPageResults() as $record)
        {
            if ($record->isManaged())
                ++$nbManaged;
        }
        $paginator->setMaxPerPage($oldMaxPerPage);
        // *******************************************************
        
        return $this->render($this->entity['templates']['list'], array(
            'nbManaged' => $nbManaged,
            'paginator' => $paginator,
            'fields' => $fields,
            'markets' => $markets,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }
    
    protected function listAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_LIST);

        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $this->dispatch(EasyAdminEvents::POST_LIST, array('paginator' => $paginator));
  
        $userContext = $this->get('session')->get('userContext');
        $markets = $this->em->getRepository('App\Entity\Market')->findAllWithFilter($userContext);
        
        return $this->render($this->entity['templates']['list'], array(
            'paginator' => $paginator,
            'fields' => $fields,
            'markets' => $markets,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        ));
    }
 
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        $em = $this->getDoctrine()->getManagerForClass($this->entity['class']);
        
        $withNullAmount = $this->request->query->get('withNullAmount');
        $market = $this->request->query->get('market');
        $checkinBegin = $this->request->query->get('checkinBegin');
        $checkinEnd = $this->request->query->get('checkinEnd');
        $withDiscountAmount = $this->request->query->get('withDiscountAmount');
        $canceledResa = $this->request->query->get('canceledResa');
        
        $queryBuilder = $em->createQueryBuilder()
            ->select('entity')
            ->from($this->entity['class'], 'entity')
            ->join('entity.property', 'property')
            ->orWhere('LOWER(property.label) LIKE :query')
            ->setParameter('query', '%'.strtolower($searchQuery).'%')
        ;
        
        if ($withNullAmount == "on")
        {
            $queryBuilder->andWhere('entity.hostProfitAmount is null or entity.hostProfitAmount = 0');
        }
        
        if ($withDiscountAmount == "on")
        {
            $queryBuilder->andWhere('entity.discountAmount is not null');
        }
        
        if ($canceledResa == "on")
        {
            $queryBuilder->andWhere("entity.status in ('".Reservation::STATUS_CANCELED."')");
        }
        else
        {
            $queryBuilder->andWhere("entity.status in ('".Reservation::STATUS_CONFIRMED."')");
        }
        
        if ($market != "")
        {
            $queryBuilder->andWhere("property.market = $market");
        }
        
        if ($checkinBegin != "")
        {
            $queryBuilder->andWhere("entity.checkinDate >= '$checkinBegin'");
        }
        
        if ($checkinEnd != "")
        {
            $queryBuilder->andWhere("entity.checkinDate <= '$checkinEnd'");
        }
        
        if (!empty($dqlFilter)) {
            $queryBuilder->andWhere($dqlFilter);
        }
        
        if (null !== $sortField) {
            $queryBuilder->orderBy('entity.'.$sortField, $sortDirection ?: 'DESC');
        }
        
        //$sql = $queryBuilder->getQuery()->getSQL();
        //echo $sql;
            
        return $queryBuilder;        
    }
    
    public function createEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        
        $userContext = $this->get('session')->get('userContext');
        
        if ($view == "new")
        {
            $formBuilder = AdminController::applyFilterFor($formBuilder, $userContext, AdminController::PROPERTY_FIELD);
        }
        else
        {
            // Force editing the host profit price when modifying the checkin or checkout date
            $formBuilder->add('hostProfitAmount',  MoneyType::class, array(
                'label' => 'Host profit',
                'required' => true,
                'divisor' => 100,
                'data' => null,
                )
            );
        }
        
        return $formBuilder;
    }
}