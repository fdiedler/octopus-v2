<?php

namespace App\Repository;

use App\Entity\Market;
use Doctrine\ORM\EntityRepository;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class MarketObjectiveRepository extends FilterRepository
{   
    public function findObjectiveByMarketAndDate(Market $market, \DateTime $objectiveDate, $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('mo');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->andWhere("mo.market = :market")
            ->andWhere("mo.date = :objectiveDate")
            ->andWhere("mo.type = 0")
            ->setParameter("objectiveDate", $objectiveDate)
            ->setParameter("market", $market)
        ;
        
        $res = $queryBuilder->getQuery()->getResult();
        
        return (count($res) == 1 ? $res[0] : null);
    }
    
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'mo')
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
}
