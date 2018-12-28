<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\Reservation;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use DateTime;
use App\Security\UserContext;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class ReservationRepository extends FilterRepository
{

    public function findManagedByPeriodAndStatuses(DateTime $start, DateTime $end, array $statuses, ?UserContext $userContext, bool $useCheckoutForPeriod = false)
    {
        // Sometimes we need to query reservation on Checkout date instead of Checkin Date
        $columnUsedForPeriod = "checkinDate";
        if ($useCheckoutForPeriod)
            $columnUsedForPeriod = "checkoutDate";
        
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->andWhere("r.$columnUsedForPeriod between :start and :end")
            ->andWhere("r.status IN (:statuses)")
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', $statuses)
        ;

        $queryBuilder->addOrderBy('_p.label', 'ASC');
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findManagedByPeriodAndMarket(DateTime $start, DateTime $end, int $marketId, ?UserContext $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->andWhere("r.checkinDate between :start and :end")
            ->andWhere("r.status IN (:statuses)")
            ->andWhere("_p.market = :marketId")
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('marketId', $marketId)
            ->setParameter('statuses', Reservation::STATUS_CONFIRMED)
        ;
        
        return $queryBuilder->getQuery()->getResult();
    }

    // Use in InvoiceManager
    public function findManagedByPropertyAndPeriod(Property $property, DateTime $dateStart, DateTime $dateEnd, ?UserContext $userContext = null)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->andWhere('r.checkinDate between :start and :end')
            ->andWhere('r.property = :property')
            ->orderBy('r.checkinDate')
            ->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->setParameter('property', $property)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function findAllMonthsWithReservationsByYear(?UserContext $userContext = null)
    {
        $query = $this->createQueryBuilder('r')
            ->select('distinct year(r.checkinDate), month(r.checkinDate)')
            ->groupBy('r.checkinDate')
            ->orderBy('year(r.checkinDate), month(r.checkinDate)')
            ->getQuery();

        $res = $query->getResult();

        $years = [];
        foreach($res as $row) {
            $years[$row[1]][] = $row[2];
        }
        return $years;
    }

    // Only use by Airbnb bot - no need to filter
    public function findByPropertyNotMatchingIds(Property $property, array $excludeIds)
    {
        $query = $this->createQueryBuilder('r')
            ->where('r.property = :property')
            ->andWhere('r.airbnbId NOT IN (:excludeIds)')
            ->andWhere('r.reference != \'\'') // reference is empty for all reservations that are not managed by AirBNB
            ->setParameter('property', $property)
            ->setParameter('excludeIds', $excludeIds)
            ->getQuery()
        ;

        return $query->getResult();
    }

    // Use in the Export domain
    public function findAllManagedQueryWithCompleteGraph(?UserContext $userContext = null)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->join('_p.market', 'm')
            ->orderBy('month(r.checkinDate), _p.label, r.checkinDate')
        ;

        return $queryBuilder->getQuery();
    }

    public function findCleaningBeforeCheckout($userContext)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->join("App:Cleaning", "c", \Doctrine\ORM\Query\Expr\Join::WITH, "r.id = c.reservation")
            ->andWhere('r.checkoutDate > c.date')
		;
       
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findAllManagedWithNoCleaning(DateTime $start, DateTime $end, $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->leftjoin("App:Cleaning", "c", \Doctrine\ORM\Query\Expr\Join::WITH, "r.id = c.reservation")
            ->andWhere('c.date is null')
            ->andWhere('r.status in (:status)')
            ->andWhere("r.checkinDate between :start and :end")
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
		;
   
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findAllManagedWithNoCheckin(DateTime $start, DateTime $end, $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->leftjoin("App:Checkin", "ci", \Doctrine\ORM\Query\Expr\Join::WITH, "r.id = ci.reservation")
            ->andWhere('ci.date is null')
            ->andWhere('r.status in (:status)')
            ->andWhere("r.checkinDate between :start and :end")
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
		;
   
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function getNumberOfNightsForOneProperty(Property $property, DateTime $start, DateTime $end, $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, 'r', true)
            ->select('SUM(DATE_DIFF(r.checkoutDate, r.checkinDate)) as nbNights')
            ->andWhere('r.property = :property')
            ->andWhere('r.status in (:status)')
            ->andWhere('r.checkinDate between :start and :end')
            ->setParameter('property', $property)
            ->setParameter('status', Reservation::STATUS_CONFIRMED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
        ;
        
        $query = $queryBuilder->getQuery();
        $result = $query->getResult();
    
        return (count($result) == 1 ? $result[0]["nbNights"] : null);
    }
    
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'r', $managed = false)
    {   
        $queryBuilder = $queryBuilder
            ->join("$alias.property", "_p")
        ;
        
        // Add filter to work only on managed reservations
        if ($managed)
        {
            $queryBuilder = $queryBuilder
                ->join("_p.managedPeriods", "_mp")
                ->andWhere("_mp.start <= $alias.checkinDate and (_mp.end is null or _mp.end >= $alias.checkinDate)")
            ;
        }
        
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
