<?php

namespace App\Entity;

use App\Domain\MoneyWithTax;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Money\Currency;
use DateTime;

/**
 * A cleaning associated to a reservation
 *
 * @ORM\Entity(repositoryClass="App\Repository\CleaningRepository")
 * @ORM\Table(name="cleaning")
 */
class Cleaning
{
    const CLEANING_DATA = [
        "NoCleaning"                => ["type" => -1, "label" => "No cleaning", "func" => ""],
        "Standard"                  => ["type" => 0, "label" => "Standard", "func" => "getCleaningFeeAmount"],
        "Deep"                      => ["type" => 1, "label" => "Deep cleaning", "func" => "getDeepCleaningFeeAmount"],
        "Bedding"                   => ["type" => 2, "label" => "Bedding", "func" => "getBeddingFeeAmount"],
        "StandardWithoutBedding"    => ["type" => 3, "label" => "Standard without Bedding", "func" => "getCleaningFeeAmountWithoutBedding"],
    ];

    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
     * Type of the cleaning amoung Standard, Deep
     *
     * @ORM\Column(type="integer")
     * @var int
     */
    private $type;

    /**
     * Corresponding reservation
     *
     * @ORM\ManyToOne(targetEntity="Reservation", inversedBy="Cleaning")
     * @ORM\JoinColumn(nullable=true)
     * @var Reservation
     */
    private $reservation;
    
    /**
     * Corresponding property
     *
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="Cleaning")
     * @ORM\JoinColumn(nullable=false)
     * @var Property
     */
    private $property;

    /**
     * The cleaning presta that owns this cleaning
     *
     * @ORM\ManyToOne(targetEntity="CleaningPresta", inversedBy="cleanings")
     * @ORM\JoinColumn(nullable=true)
     * @var CleaningPresta
     */
    private $presta;
    
    /**
     * Amount (without taxes, euros, cents).
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $amountWithoutTaxes;

    /**
     * Date
     *
     * @ORM\Column(type="date", nullable=true)
     * @var DateTime
     */
    private $date;
    
    /**
     * Details about the cleaning.
     *
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $comments;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getType(): ?int
    {
        return $this->type;
    }
    
    /**
     * @return string
     */
    public function getCorrelType(): ?string
    {
        foreach (self::CLEANING_DATA as $cleaning)
        {
            if ($cleaning["type"] == $this->type)
                return $cleaning["label"];
        }
        return null;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): Cleaning
    {
        $this->type = $type;
        return $this;
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
    public function setReservation(Reservation $reservation): Cleaning
    {
        $this->reservation = $reservation;
        return $this;
    }
    
    /**
     * @return Property
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }

    /**
     * @param Property $property
     */
    public function setProperty(Property $property): Cleaning
    {
        $this->property = $property;
        return $this;
    }
    
    /**
     * @return CleaningPresta
     */
    public function getPresta(): ?CleaningPresta
    {
        return $this->presta;
    }

    /**
     * @param CleaningPresta $presta
     */
    public function setPresta(?CleaningPresta $presta): Cleaning
    {
        $this->presta = $presta;
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
    public function setDate(?DateTime $date): Cleaning
    {
        $this->date = $date;
        return $this;
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
    public function setAmountWithoutTaxes(string $amount): Cleaning
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
     * @return Cleaning
     */
    public function setAmountWithoutTaxesAsMoney(Money $money): Cleaning
    {
        $this->checkCurrency($money->getCurrency());
        $this->setAmountWithoutTaxes($money->getAmount());
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
    public function setComments(?string $comments): Cleaning
    {
        $this->comments = $comments;
        return $this;
    }

    public function __toString()
    {
        return (string) $this->type;
    }

    private function checkCurrency(Currency $currency)
    {
        if($currency->getCode() != "EUR") {
            throw new \LogicException("Only euros are supported right now");
        }
    }
}
