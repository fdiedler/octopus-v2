<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\ORM\EntityRepository;
use App\Security\UserContext;
use DateTime;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class ExtraChargeRepository extends FilterRepository
{

    public function findByPeriod(DateTime $start, DateTime $end, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('ec');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->andWhere('ec.date between :start and :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'ec')
    {   
        $queryBuilder = $queryBuilder
            ->join("$alias.property", "_p")
        ;
        
        // No filter for superadmin
        if ($this->isSuperAdmin($userContext))
            return $queryBuilder;
        
        // Add filters according to usercontext
        $queryBuilder = $queryBuilder
            ->andWhere("_p.market in (:markets)")
            ->setParameter('markets', $userContext->getAllowedMarketId())
        ;
        
        return $queryBuilder;
    }
}
