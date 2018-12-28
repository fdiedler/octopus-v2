<?php

use Behat\Behat\Context\Context;
use Symfony\Component\HttpKernel\KernelInterface;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Behat context with assertions using PHPUnit engine
 *
 * A clean database context is rebuilt before each scenario.
 * 
 * @see http://behat.org/en/latest/quick_start.html
 */
class FeatureContext implements Context
{

    // there is a trait per domain so that the number of fixtures can scale
    use AirbnbContextTrait;
    use AirbnbResaContextTrait;
    use ExportsContextTrait;
    use HostingContextTrait;
    use InvoicingContextTrait;
    use OutgoingHttpTrait;
    use ReportingContextTrait;
    use TimeContextTrait;
    use UserContextTrait;
    use AccessTrait;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Helper for retrieving a service from the container.
     */
    public function get($name)
    {
        return $this->getContainer()->get($name);
    }

    /**
     * Helper for retrieving the current user context.
     */
    public function getUserContext($userEmail)
    {
        $allUserContext = $this->get('session')->get('allUserContext');
        $userContext = null;
        
        // Found the right user context
        foreach ($allUserContext as $id => $_userContext)
        {
             if ($_userContext->getCurrentUser() == $userEmail)
             {
                 $userContext = $_userContext;
                 break;
             }
        }
        
        return $userContext;
    }
    
    // ==================================== Entity Management =====================================

    /**
     * Return the object persistence manager.
     *
     * @return Doctrine\Common\Persistence\ObjectManager
     */
    public function getEntityManager(): ObjectManager
    {
        return $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * Return the repository for given entity.
     */
    public function getRepository($name)
    {
        return $this->getEntityManager()->getRepository($name);
    }

    /**
     * Fetches objects by their main attribute/reference.
     */
    public function find($entityName, $value, $attribute = null)
    {
        if (is_null($attribute)) {
            $attributeQuery = '';
        } else {
            $attributeQuery = '.'.$attribute;
        }

        $repository = $this->getRepository($entityName);

        $qb = $repository->createQueryBuilder('e')
            ->where('e'.$attributeQuery.' = :value')
            ->setParameter('value', $value)
        ;

        try {
            $entity = $qb->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            throw new LogicException(
                sprintf(
                    "Unable to find a $entityName with the $attribute '%s'", $value
                )
            );
        }

        return $entity;
    }

    /**
     * Persists the given entity and flushes immediately.
     */
    public function persistAndFlush($entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    // ==================================== Suite Lifecycle Hooks =====================================

    /**
     * @BeforeSuite
     */
    public static function clearCache(BeforeSuiteScope $scope)
    {
        $cacheDir = dirname(__DIR__).'../../var/cache/test';
        $fs = new Filesystem();
        try {
            $fs->remove($cacheDir.'/*');
        } catch (IOException $e) {
            throw new \Exception(sprintf('Unable to clear the test application cache at "%s"', $cacheDir));
        }
    }

    /**
     * @BeforeScenario
     */
    public function buildSchema(BeforeScenarioScope $scope)
    {
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if (!empty($metadata)) {
            $tool = new SchemaTool($entityManager);
            $tool->dropSchema($metadata);
            $tool->createSchema($metadata);
        }
    }

    /**
     * @AfterStep
     *
     * Clear doctrine entity manager
     */
    public function clearEntityManager()
    {
        $this->getEntityManager()->clear();
    }

}
