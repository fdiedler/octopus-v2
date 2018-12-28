<?php

namespace App\EasyAdmin;

use JavierEguiluz\Bundle\EasyAdminBundle\Cache\CacheManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager as BaseConfigManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class ConfigManager extends BaseConfigManager
{

    use RoleActionMappingTrait;

    private $authorizationChecker;

    public function __construct(
        CacheManager $cacheManager,
        PropertyAccessorInterface $propertyAccessor,
        array $originalBackendConfig,
        $debug,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($cacheManager, $propertyAccessor, $originalBackendConfig, $debug);
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getEntityConfig($entityName)
    {
        $config = parent::getEntityConfig($entityName);

        foreach($this->getAllPossibleActions() as $action)
        {
            $expectedRole = $this->getExpectedRole($action, $entityName);
            if(!$this->authorizationChecker->isGranted($expectedRole)) {
                $config['disabled_actions'][] = $action;
            }
        }

        return $config;
    }

}
