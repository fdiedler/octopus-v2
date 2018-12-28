<?php

namespace App\Entity;

use App\Domain\MoneyWithTax;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Money\Money;

/**
 * A checkin associated to a reservation
 *
 * @ORM\Entity(repositoryClass="App\Repository\CheckinRepository")
 * @ORM\Table(name="checkin")
 */
class Checkin
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
     * Corresponding reservation
     *
     * @ORM\ManyToOne(targetEntity="Reservation", inversedBy="checkins")
     * @ORM\JoinColumn(nullable=true)
     * @var Reservation
     */
    private $reservation;
   
   /**
     * The checker that owns this checkin
     *
     * @ORM\ManyToOne(targetEntity="Checker", inversedBy="checkins")
     * @ORM\JoinColumn(nullable=true)
     * @var Checker
     */
    private $checker;
    
    /**
     * Date
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $date;
    
    /**
     * Time
     *
     * @ORM\Column(type="time")
     * @var DateTime
     */
    private $time;
    
    /**
     * Details about this checkin.
     *
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $comments;
    
    /**
     * Cost for a checkin (without taxes, euros, cents).
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $amountWithoutTaxes;
    
    public function __construct()
    {
        $this->date = new DateTime();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Reservation
     */
    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    /**
     * @param Reservation $reservation
     */
    public function setReservation(Reservation $reservation): Checkin
    {
        $this->reservation = $reservation;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date): Checkin
    {
        $this->date = $date;
        return $this;
    }
    
    /**
     * @return DateTime
     */
    public function getTime(): ?DateTime
    {
        return $this->time;
    }

    /**
     * @param DateTime $date
     */
    public function setTime(DateTime $time): Checkin
    {
        $this->time = $time;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     */
    public function setComments(?string $comments): Checkin
    {
        $this->comments = $comments;
        return $this;
    }

    /**
     * @return Checker
     */
    public function getChecker(): ?Checker
    {
        return $this->checker;
    }

    /**
     * @param Checker $checker
     */
    public function setChecker(?Checker $checker): Checkin
    {
        $this->checker = $checker;
        return $this;
    }
    
    /**
     * @return Property
     */
    public function getProperty(): ?Property
    {
        return $this->reservation->getProperty();
    }
    
    /**
     * @return string
     */
    public function getAmountWithoutTaxes(): ?string
    {
        return $this->amountWithoutTaxes;
    }

    /**
     * @param string $amount
     */
    public function setAmountWithoutTaxes(string $amount): Checkin
    {
        $this->amountWithoutTaxes = $amount;
        return $this;
    }
    
    /**
     * @return Money
     */
    public function getAmountWithoutTaxesAsMoney(): Money
    {
        return Money::EUR($this->amountWithoutTaxes);
    }
    
    /**
     * @param Money $money
     * @return Checkin
     */
    public function setAmountWithoutTaxesAsMoney(Money $money): Checkin
    {
        $this->setAmountWithoutTaxes($money->getAmount());
        return $this;
    }
    
    public function __toString()
    {
        return (string) $this->reservation->getProperty()->getLabel()." - ".$this->checker->getName();
    }
}
