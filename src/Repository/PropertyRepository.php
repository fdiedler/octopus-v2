<?php

namespace App\Repository;

use App\Entity\Host;
use App\Entity\Property;
use Doctrine\ORM\EntityRepository;
use DateTime;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class PropertyRepository extends FilterRepository
{
    public function addFilterConstraints($queryBuilder, $userContext, $alias = 'p')
    {
        // No filter for superadmin
        if ($this->isSuperAdmin($userContext))
            return $queryBuilder;
        
        // Add filters according to usercontext
        $queryBuilder = $queryBuilder
            ->andWhere("$alias.market in (:markets)")
            ->setParameter('markets', $userContext->getAllowedMarketId())
        ;
        
        return $queryBuilder;
    }
}
