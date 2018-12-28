<?php

namespace App\EasyAdmin;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class CompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        // replace the ConfigManager with a custom one
        $definition = $container->getDefinition('easyadmin.config.manager');
        $definition->setClass(ConfigManager::class);
        $definition->addArgument(new Reference('security.authorization_checker'));
    }

}
