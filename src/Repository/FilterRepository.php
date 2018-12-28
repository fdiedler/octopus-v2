<?php

namespace App\Repository;

use App\Entity\Market;
use Doctrine\ORM\EntityRepository;
use DateTime;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class FilterRepository extends EntityRepository
{
    
    public function findAll()
    {
        throw new \Exception("Please do not use findAll() function !");
    }
    
    public function findBy(array $criteria, ?array $orderBy = NULL, $limit = NULL, $offset = NULL)
    {
        throw new \Exception("Please do not use findBy() function !");
    }
    
    public function findOneBy(array $criteria, ?array $orderBy = [])
    {
        throw new \Exception("Please do not use findOneBy() function !");
    }
    
    public function findAllWithFilter($userContext)
    {
        return $this->findByWithFilter([], $userContext);
    }
    
    public function findByWithFilter($criteria, $userContext, $alias = "_entity")
    {
        // Create basic query
        $queryBuilder = $this->createQueryBuilder($alias);
        
        // Add filter constraints depending of the user context
        $queryBuilder = $this->addFilterConstraints($queryBuilder, $userContext, $alias);

        // Add search criteria
        foreach ($criteria as $key => $val)
        {
            if ($val != null)
            {
                $queryBuilder
                    ->andWhere("$alias.$key = :val")
                    ->setParameter("val", $val)
                ;
            }
            else
            {
                $queryBuilder->andWhere("$alias.$key is null");
            }
        }
        
        //echo $queryBuilder->getQuery()->getSQL(); die();
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function findOneByWithFilter($criteria, $userContext, $alias = "_entity")
    {
        $result = $this->findByWithFilter($criteria, $userContext, $alias);
        return (count($result) == 1 ? $result[0] : null);
    }
    
    public function isSuperAdmin($userContext)
    {
        // If not usercontext (like for behat test) -> no filter 
        if (!$userContext)
            return true;
        
        // No filter for superadmin
        if ($userContext->isSuperAdmin())
            return true;
        
        return false;
    }
}
