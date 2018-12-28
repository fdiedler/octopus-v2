<?php

namespace App\Repository;

use App\Entity\Cron;
use Doctrine\ORM\EntityRepository;
use DateTime;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class CronRepository extends EntityRepository
{
    public function findJobNotFinished()
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.finished = 0')
            ->getQuery()
        ;

        return $query->getResult();
    }
}
