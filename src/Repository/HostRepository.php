<?php

namespace App\Repository;

use App\Entity\Host;
use App\Entity\Property;
use Doctrine\ORM\EntityRepository;
use DateTime;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class HostRepository extends FilterRepository
{
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'u')
    {
        // No filter for superadmin
        if ($this->isSuperAdmin($userContext))
            return $queryBuilder;
        
        // Add filters according to usercontext
        // For hosts, filter by host : 
        //          -> holding at least one property in one of allowed markets
        //          -> or holding no property
        $queryBuilder = $queryBuilder
            ->leftjoin("App:Property", "p", \Doctrine\ORM\Query\Expr\Join::WITH, "$alias.id = p.host")
            ->andWhere("p.market in (:markets) OR p.market is null")
            ->setParameter('markets', $userContext->getAllowedMarketId())
        ;
        
        return $queryBuilder;
    }
    
    /**
     * Called to check the uniqueness of a field. See UniqueEntity annotations in Host entity file.
     *
     * @param array $criteria
     * @return array
     */
    public function testUniqueEmail(array $criteria)
    {
        // Need to check in all hosts. Passing null for userContext allows to query with no filters (like admin)
        return $this->findByWithFilter($criteria, null);
    }
}
