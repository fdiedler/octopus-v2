<?php

namespace App\Domain;

use Money\Money;
use DateTime;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\Reservation;
use App\Entity\Market;
use App\Entity\Property;
use App\Entity\Cleaning;
use App\Security\UserContext;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 * Used for external users like Checkers and Cleaning providers
 */
class ReportingOutside
{
    private $reservationRepository;
    private $marketRepository;
    private $checkinRepository;
    private $cleaningRepository;
    private $propertyRepository;
    private $currentUser;
    
    public function __construct(TokenStorageInterface $tokenStorage, ManagerRegistry $doctrine)
    {
        $this->reservationRepository = $doctrine->getRepository('App\Entity\Reservation');
        $this->checkinRepository = $doctrine->getRepository('App\Entity\Checkin');
        $this->cleaningRepository = $doctrine->getRepository('App\Entity\Cleaning');
        $this->marketRepository = $doctrine->getRepository('App\Entity\Market');
        $this->propertyRepository = $doctrine->getRepository('App\Entity\Property');
        $this->currentUser = $tokenStorage->getToken()->getUser();
    }
    
    public function getReportingCheckinsForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $checkins = $this->checkinRepository->findByUserAndPeriod($start, $end, $this->currentUser, $userContext);
        return $checkins;
    }
    
    public function getReportingCleaningForPeriod(DateTime $start, DateTime $end, UserContext $userContext)
    {
        $cleanings = $this->cleaningRepository->findByUserAndPeriod($start, $end, $this->currentUser, $userContext);
        return $cleanings;
    }
  
}
