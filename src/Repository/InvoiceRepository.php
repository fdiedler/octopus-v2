<?php

namespace App\Repository;

use App\Domain\MoneyWithTax;
use Doctrine\ORM\EntityRepository;
use Money\Money;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class InvoiceRepository extends FilterRepository
{ 
    public function findAllGroups($userContext)
    {
        $queryBuilder = $this->createQueryBuilder('i');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->select('i.billingDate as billingDate')
            ->join('_p.host','h')
            ->join("_p.market", "m")
            ->addSelect('m.name as marketName')
            ->addSelect('m.id as marketId')
            ->addSelect('count(i) as invoicesCount')
            ->addSelect('count(distinct h) as hostCount')
            ->addSelect('sum(i.amountWithoutTaxes) as amountTotal')
            ->addSelect('sum(i.taxesAmount) as taxesTotal')
            ->groupBy('i.billingDate, m.name, m.id')
            ->orderBy('i.billingDate', 'DESC')
        ;
        
        $query = $queryBuilder->getQuery();
        
        $data = [];
        foreach($query->getResult() as $row) {
            $row['total'] = new MoneyWithTax(Money::EUR($row['amountTotal']), Money::EUR($row['taxesTotal']));
            $billingDate = $row['billingDate']->format("Y-m-d");
            $marketName = $row['marketName'];
            $data[$billingDate]['billingDate'] = $row['billingDate'];
            
            // Group data by market
            $data[$billingDate]['markets'][$marketName] = $row;
            
            // Compute totals for all markets
            if (!isset($data[$billingDate]['invoicesCount']))
                $data[$billingDate]['invoicesCount'] = 0;
            $data[$billingDate]['invoicesCount'] += $row['invoicesCount'];
            
            if (!isset($data[$billingDate]['hostCount']))
                $data[$billingDate]['hostCount'] = 0;
            $data[$billingDate]['hostCount'] += $row['hostCount'];
            
            if (!isset($data[$billingDate]['total']))
                $data[$billingDate]['total'] = new MoneyWithTax(Money::EUR(0), Money::EUR(0));
            $data[$billingDate]['total'] =
                $data[$billingDate]['total']->add(new MoneyWithTax(Money::EUR($row['amountTotal']), Money::EUR($row['taxesTotal'])));
        }

        return $data;
    }

    public function deleteAllByDate(\DateTime $date)
    {
        // Not possible to use join with delete statement
        $queryBuilder = $this->createQueryBuilder('i')
            ->delete()
            ->andWhere('i.billingDate = :date')
            ->setParameter('date', $date)
        ;
        
        $query = $queryBuilder->getQuery();
        $query->execute();
    }
    
    public function getInvoiceByDate(string $date, $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('i');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->andWhere('i.billingDate = :date')
            ->setParameter('date', $date)
            ->orderBy('i.reference', 'ASC')
        ;
        
        $query = $queryBuilder->getQuery();
        
        return $query->getResult();
    }
   
    public function getInvoiceByDateAndMarket(string $date, int $marketId, $userContext)
    {
        $queryBuilder = $this->createQueryBuilder('i');
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext)
            ->join('i.property', 'p')
            ->join('p.host','h')
            ->andWhere('i.billingDate = :date')
            ->setParameter('date', $date)
            ->orderBy('i.reference', 'ASC')
        ;
        
        if ($marketId != -1)
        {
            $queryBuilder = $queryBuilder
                ->join('p.market','m')
                ->andWhere('m.id in (:marketId)')
                ->setParameter('marketId', $marketId)
            ;
        }
        
        $query = $queryBuilder->getQuery();
        
        return $query->getResult();
    }
   
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'i')
    {   
        $queryBuilder = $queryBuilder
            ->join("$alias.property", "_p");
            
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
