<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Host;
use App\Entity\Property;
use App\Entity\Reservation;
use App\Entity\Checker;
use Doctrine\ORM\EntityRepository;
use DateTime;
use App\Security\UserContext;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class CheckinRepository extends FilterRepository
{
    public function findByPeriod(DateTime $dateStart, DateTime $dateEnd, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('ci');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->andWhere('ci.date between :start and :end')
			->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
		;
       
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findByUserAndPeriod(DateTime $dateStart, DateTime $dateEnd, User $checker, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('ci');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->join('ci.checker', 'c')
            ->andWhere('ci.date between :start and :end')
            ->andWhere('c.user = :user')
			->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->setParameter('user', $checker)
		;
        
        $queryBuilder->addOrderBy('_p.label', 'ASC');
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findByCheckerAndPeriod(DateTime $dateStart, DateTime $dateEnd, ?Checker $checker, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('ci');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->andWhere('ci.date between :start and :end')
            ->andWhere('ci.checker = :checker')
			->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->setParameter('checker', $checker)
		;
        
        $queryBuilder->addOrderBy('_p.label', 'ASC');
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'ci')
    {   
        $queryBuilder = $queryBuilder
            ->join("$alias.reservation", "_r")
            ->join("_r.property", "_p")
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
