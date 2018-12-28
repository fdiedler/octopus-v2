<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class MarketRepository extends FilterRepository
{
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'm')
    {
        // No filter for superadmin
        if ($this->isSuperAdmin($userContext))
            return $queryBuilder;
        
        // Add filters according to usercontext
        $queryBuilder = $queryBuilder
            ->where("$alias.id in (:markets)")
            ->setParameter('markets', $userContext->getAllowedMarketId())
        ;
        
        return $queryBuilder;
    }
    
    /**
     * Called to check the uniqueness of a field. See UniqueEntity annotations in Market entity file.
     *
     * @param array $criteria
     * @return array
     */
    public function testUniqueName(array $criteria)
    {
        return $this->findByWithFilter($criteria, null);
    }
}
