<?php

namespace App\Entity;

use App\Domain\MoneyWithTax;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Money\Currency;
use DateTime;

/**
 * A exceptional charge or business gesture.
 *
 * @ORM\Entity(repositoryClass="App\Repository\ExtraChargeRepository")
 * @ORM\Table(name="extra_charge")
 */
class ExtraCharge
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id ;

    /**
     * Description. Ex: "Replacement of the broken chair"
     *
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $label ;

    /**
     * Corresponding reservation
     *
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="extraCharges")
     * @ORM\JoinColumn(nullable=false)
     * @var Property
     */
    private $property;

    /**
     * Date
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $date;

    /**
     * Amount (without taxes, euros, cents).
     * May be negative in the case of a commercial gesture.
     *
     * @ORM\Column(type="string", length=10)
     * @var integer
     */
    private $amountWithoutTaxes ;

    /**
     * Amount of taxes (euros, cents)
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $taxesAmount;

    /**
     * Details about the charge.
     *
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $comments ;

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
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): ExtraCharge
    {
        $this->label = $label;
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
    public function setProperty(Property $property): ExtraCharge
    {
        $this->property = $property;
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
    public function setDate(DateTime $date): ExtraCharge
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
    public function setAmountWithoutTaxes(string $amount): ExtraCharge
    {
        $this->amountWithoutTaxes = $amount;
        $this->checkAmountsConsistency();
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
     * @return ExtraCharge
     */
    public function setAmountWithoutTaxesAsMoney(Money $money): ExtraCharge
    {
        $this->checkCurrency($money->getCurrency());
        $this->setAmountWithoutTaxes($money->getAmount());
        return $this;
    }

    /**
     * @return string
     */
    public function getTaxesAmount(): ?string
    {
        return $this->taxesAmount;
    }

    /**
     * @param string $taxesAmount
     */
    public function setTaxesAmount(string $taxesAmount)
    {
        $this->taxesAmount = $taxesAmount;
        $this->checkAmountsConsistency();
    }

    /**
     * @return Money
     */
    public function getTaxesAmountAsMoney(): Money
    {
        return Money::EUR($this->taxesAmount);
    }

    /**
     * @param Money $money
     * @return ExtraCharge
     */
    public function setTaxesAmountAsMoney(Money $money): ExtraCharge
    {
        $this->checkCurrency($money->getCurrency());
        $this->setTaxesAmount($money->getAmount());
        return $this;
    }

    /**
     * @return MoneyWithTax
     */
    public function getAmountWithTaxes(): MoneyWithTax
    {
        return new MoneyWithTax($this->getAmountWithoutTaxesAsMoney(), $this->getTaxesAmountAsMoney());
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
    public function setComments(string $comments): ExtraCharge
    {
        $this->comments = $comments;
        return $this;
    }

    public function __toString()
    {
        return (string) $this->label;
    }

    private function checkCurrency(Currency $currency)
    {
        if($currency->getCode() != "EUR") {
            throw new \LogicException("Only euros are supported right now");
        }
    }

    private function checkAmountsConsistency()
    {
        if(null == $this->taxesAmount) return;
        if(null == $this->amountWithoutTaxes) return;
        $a = $this->moneySignToInt($this->getAmountWithoutTaxesAsMoney());
        $tx = $this->moneySignToInt($this->getTaxesAmountAsMoney());
        if($a * $tx < 0) {
            throw new \LogicException("Amount/tax sign mismatch");
        }
    }

    private function moneySignToInt(Money $money)
    {
        return $money->isNegative() ? -1 : ($money->isPositive() ? 1 : 0);
    }

}
