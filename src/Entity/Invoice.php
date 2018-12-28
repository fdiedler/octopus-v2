<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Money\Money;

/**
 * An invoice
 *
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceRepository")
 * @ORM\Table(name="invoice")
 */
class Invoice
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
     * WeHost reference number
     *
     * @ORM\Column(type="string", length=16)
     * @var string
     */
    private $reference;

    /**
     * Billing date
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $billingDate ;

    /**
     * Property targetted by this invoice
     *
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="invoices")
     * @ORM\JoinColumn(nullable=false)
     * @var Property
     */
    private $property ;

    /**
     * Amount without taxes (euros, cents)
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $amountWithoutTaxes ;

    /**
     * Amount of taxes (euros, cents)
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $taxesAmount ;

    /**
     * Details of the invoice
     *
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    private $details ;
    
    /**
     * @ORM\Column(name="flag", type="boolean")
     */
    private $done;

    public function __construct(string $reference, Property $property)
    {
        $this->reference = $reference;
        $this->property = $property;
        $this->done = false;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @return DateTime
     */
    public function getBillingDate(): DateTime
    {
        return $this->billingDate;
    }

    /**
     * @param DateTime $billingDate
     * @return Invoice
     */
    public function setBillingDate(DateTime $billingDate): Invoice
    {
        $this->billingDate = $billingDate;
        return $this;
    }

    /**
     * @return Property
     */
    public function getProperty(): Property
    {
        return $this->property;
    }

    /**
     * @param Property $property
     */
    public function setProperty(Property $property): Invoice
    {
        $this->property = $property;
        return $this->property;
    }

    /**
     * @return Money
     */
    public function getAmountWithoutTaxes(): Money
    {
        return Money::EUR($this->amountWithoutTaxes);
    }

    /**
     * @param Money $amountWithoutTaxes
     * @return Invoice
     */
    public function setAmountWithoutTaxes(Money $amountWithoutTaxes): Invoice
    {
        $this->amountWithoutTaxes = $amountWithoutTaxes->getAmount();
        return $this;
    }

    /**
     * @return Money
     */
    public function getTaxesAmount(): Money
    {
        return Money::EUR($this->taxesAmount);
    }

    /**
     * @param Money $taxesAmount
     * @return Invoice
     */
    public function setTaxesAmount(Money $taxesAmount): Invoice
    {
        $this->taxesAmount = $taxesAmount->getAmount();
        return $this;
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array $details
     * @return Invoice
     */
    public function setDetails(array $details): Invoice
    {
        $this->details = $details;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isDone()
    {
        return $this->done;
    }

    /**
     * @param bool $done
     */
    public function setDone(bool $done) : Invoice
    {
        $this->done = $done;
        return $this;
    }

    public function __toString()
    {
        return "[$this->reference] $this->amountWithoutTaxes ($this->taxesAmount) EUR";
    }

}
