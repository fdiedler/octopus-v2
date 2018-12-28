<?php

namespace App\Repository;

use App\Entity\Market;
use Doctrine\ORM\EntityRepository;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class CleaningPrestaRepository extends FilterRepository
{
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'cp')
    {
        // No filter for superadmin
        if ($this->isSuperAdmin($userContext))
            return $queryBuilder;
        
        // Add filters according to usercontext
        $queryBuilder = $queryBuilder
            ->where("$alias.market in (:markets)")
            ->setParameter('markets', $userContext->getAllowedMarketId())
        ;
        
        return $queryBuilder;
    }
    
    /**
     * Called to check the uniqueness of a field. See UniqueEntity annotations in Cleaning entity file.
     *
     * @param array $criteria
     * @return array
     */
    public function testUniqueUser(array $criteria)
    {
        // Need to check in all hosts. Passing null for userContext allows to query with no filters (like admin)
        return $this->findByWithFilter($criteria, null);
    }
    
    /**
     * Called to check the uniqueness of a field. See UniqueEntity annotations in Cleaning entity file.
     *
     * @param array $criteria
     * @return array
     */
    public function testUniqueName(array $criteria)
    {
        // Need to check in all hosts. Passing null for userContext allows to query with no filters (like admin)
        return $this->findByWithFilter($criteria, null);
    }
}
