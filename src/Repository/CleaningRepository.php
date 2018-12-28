<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Host;
use App\Entity\Property;
use App\Entity\Reservation;
use Doctrine\ORM\EntityRepository;
use DateTime;
use App\Security\UserContext;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class CleaningRepository extends FilterRepository
{
    public function findExtraCleaningForPeriod(DateTime $dateStart, DateTime $dateEnd, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->andWhere('c.date between :start and :end')
			->andWhere('c.reservation is null')
			->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
		;
       
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findCleaningForPeriod(DateTime $dateStart, DateTime $dateEnd, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->join("c.property", 'prop')
            ->join("prop.market", 'mark')
            ->andWhere('c.date between :start and :end')
			->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
		;
       
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findByUserAndPeriod(DateTime $dateStart, DateTime $dateEnd, User $checker, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->join('c.presta', 'cp')
            ->andWhere('c.date between :start and :end')
            ->andWhere('cp.user = :user')
			->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->setParameter('user', $checker)
		;
        
        $queryBuilder->addOrderBy('_p.label', 'ASC');
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'c')
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
