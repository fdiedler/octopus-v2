<?php

namespace App\EasyAdmin;

use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Restrict the management of some entities to administrators.
 * Allow to filter data for all records managed by EasyAdmin according to the current user context.
 *
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class EventListener implements EventSubscriberInterface
{

    use RoleActionMappingTrait;

    private $authorizationChecker;
    private $user;
    
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    private function isExcludedEntity($entityName)
    {
        // Exclude some entities for global filtering
        if ($entityName === 'User' || $entityName === 'Cron')
            return true;
        
        return false;
    }

    public static function getSubscribedEvents()
    {
        return array(
            EasyAdminEvents::POST_INITIALIZE => array('checkAccess'),
            EasyAdminEvents::POST_LIST_QUERY_BUILDER => array('onListQueryBuilder'),
            EasyAdminEvents::POST_SEARCH_QUERY_BUILDER => array('onSearchQueryBuilder'),
            EasyAdminEvents::PRE_EDIT => array('onPreEdit'),
        );
    }

    public function onPreEdit(GenericEvent $event)
    {
        $subject = $event->getSubject();
        $em = $event->getArgument('em');
        $request = $event->getArgument('request');
        
        // Protect entity edition if the user hacks the URL...
        if (!$this->isExcludedEntity($subject['name']))
        {
            // Check if the user can edit this ID
            $userContext = $request->getSession()->get('userContext');
            $entityId = $request->get('id', -1);
            $entity = $em->getRepository($subject['class'])->findByWithFilter(["id" => $entityId], $userContext);
            if (count($entity) == 0)
            {
                $this->logAccess($request->get('entity'));
                throw new AccessDeniedHttpException("Hack detected - Not allowed to edit this entity !");
            }
        }
    }
    
    private function addFilterConstraints(GenericEvent $event)
    {
        $subject = $event->getSubject();
        $queryBuilder = $event->getArgument('query_builder');
        $em = $event->getArgument('em');
        
        if (!$this->isExcludedEntity($subject['name']))
        {
            // Add the filter here before querying the database
            $userContext = $event->getArgument('request')->getSession()->get('userContext');
            $queryBuilder = $em->getRepository($subject['class'])->addFilterConstraints($queryBuilder, $userContext, "entity");
        }
        
        return $queryBuilder;
    }
    
    public function onSearchQueryBuilder(GenericEvent $event)
    {
        // Add the filter when the user performs a search query
        $this->addFilterConstraints($event);
    }
    
    public function onListQueryBuilder(GenericEvent $event)
    {
        // Add the filter when the user performs a list query
        $this->addFilterConstraints($event);
    }
    
    public function checkAccess(GenericEvent $event)
    {
        $targetEntity = str_replace('App\\Entity\\','',$event->getSubject()['class']);
        $request = $event['request'];
        $action = $request->get('action') ?? 'list';

        // unauthorized access
        if(!$this->authorizationChecker->isGranted($this->getExpectedRole($action, $targetEntity))) {
            $this->logAccess($targetEntity);
            throw new AccessDeniedHttpException("Not allowed to access this entity");
        }
    }

    private function logAccess($entity)
    {
        $dumpUser = print_r($this->user, true);
        file_put_contents("accessLog.txt", "checkAccess - Not allowed to access $entity entity\n $dumpUser \n", FILE_APPEND);
    }
}

