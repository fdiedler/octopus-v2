<?php namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Money\Currency;
use Money\Money;
use DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PropertyRepository")
 * @ORM\Table(name="property")
 * @ORM\HasLifecycleCallbacks
 */
class Property
{
    const TYPE_DATA = [
        "WeHost"                => ["type" => 0, "label" => "We Host"],
        "WeHostPlus"            => ["type" => 1, "label" => "We Host Plus"],
    ];
    
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
     * The Host that owns this accomodation
     *
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="properties")
     * @ORM\JoinColumn(nullable=false)
     * @var Host
     */
    private $host;

    /**
     * The Market where this accomodation is located
     *
     * @ORM\ManyToOne(targetEntity="Market", inversedBy="properties")
     * @ORM\JoinColumn(nullable=false)
     * @var Market
     */
    private $market;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $label;
    
    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $type;

    /**
     * ID of this property ("Listing") on the Airbnb platform
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     * @var string
     */
    private $airbnbId;

    /**
     * URL of the Airbnb calendar (iCal)
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $airbnbCalendarUrl;
    
    /**
     * URL of the Access form
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $accessFormUrl;

    /**
     * Date of the last successful synchronization with Airbnb
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $lastSuccessfulAirbnbSync;

    /**
     * Log of the latest synchronization attempt with
     * Airbnb
     *
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $lastAirbnbSyncLog;

    /**
     * Periods of time when this property is managed by WeHost.
     *
     * (The uniqueness of the 2nd join column enforces the OneToMany cardinality)
     *
     * @ORM\ManyToMany(targetEntity="TimePeriod", cascade={"persist","remove"})
     * @ORM\JoinTable(name="properties_time_periods",
     *      joinColumns={@ORM\JoinColumn(name="property_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="time_period_id", referencedColumnName="id", unique=true)}
     *      )
     * @var Collection
     */
    private $managedPeriods;

    /**
     * The Reservations for this property
     *
     * @ORM\OneToMany(targetEntity="Reservation", mappedBy="property", cascade={"remove"}))
     * @var Collection
     */
    private $reservations;

    /**
     * The percentage that WeHost takes as a service fee.
     * Ex: "20"
     *
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $wehostPercentage ;

    /**
     * The amount of money charged to the *host* for the cleaning
     * (without taxes, in cents, euros)
     *
     * @ORM\Column(type="string")
     * @var string
     */
    private $cleaningFeeAmount;
    
    /**
     * The amount of money charged to the *host* for the deep cleaning
     * (without taxes, in cents, euros)
     *
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $deepCleaningFeeAmount;
    
    /**
     * The amount of money charged to the *host* for the bedding
     * (without taxes, in cents, euros)
     *
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $beddingFeeAmount;
    
    /**
     * The amount of money charged to the *host* for the cleaning without bedding
     * (without taxes, in cents, euros)
     *
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $cleaningFeeAmountWithoutBedding;

    /**
     * The amount of money that should be charged to the *guest* if the
     * corresponding amount could not be retrieved on the reservation
     * from Airbnb.
     *
     * @ORM\Column(type="string")
     * @var string
     */
    private $defaultGuestCleaningFeeAmount;
    
    /**
     * The minimum commission for WeHost if a reservation is under a threshold
     *
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $minCommissionAmount;
    
    /**
     * The minimum for a given reservation
     *
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $minReservationAmount;

    /**
     * All the properties of this host
     *
     * @ORM\OneToMany(targetEntity="ExtraCharge", mappedBy="property")
     * @var Collection
     */
    private $extraCharges ;

    /**
     * All the invoices created for this property
     *
     * @ORM\OneToMany(targetEntity="Invoice", mappedBy="property")
     * @var Collection
     */
    private $invoices ;

    /**
     * The default cleaning provider for this property
     *
     * @ORM\ManyToOne(targetEntity="CleaningPresta")
     * @ORM\JoinColumn(nullable=true)
     * @var CleaningPresta
     */
    private $defaultCleaningPresta;
    
    // Virtual property (not exists in Doctrine)
    public $nbNights;
    
    public function __construct(Host $host = null, Market $market = null)
    {
        $this->host = $host;
        $this->market = $market;
        $this->managedPeriods = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->extraCharges = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->nbNights = 0;
    }

    /**
     * @return int
     */
    public function getId()
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
     * @param int $type
     */
    public function setType(int $type): Property
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return Host
     */
    public function getHost(): ?Host
    {
        return $this->host;
    }

    /**
     * @return Host
     */
    public function setHost(Host $host): Property
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return Market
     */
    public function getMarket(): ?Market
    {
        return $this->market;
    }

    /**
     * @param Market $market
     */
    public function setMarket(Market $market)
    {
        $this->market = $market;
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
     * @return Property
     */
    public function setLabel(string $label): Property
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getAirbnbId(): ?string
    {
        return $this->airbnbId;
    }

    /**
     * @param string $airbnbId
     * @return Property
     */
    public function setAirbnbId(string $airbnbId): Property
    {
        $this->airbnbId = $airbnbId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAirbnbCalendarUrl(): ?string
    {
        return $this->airbnbCalendarUrl;
    }

    /**
     * @param string $airbnbCalendarUrl
     * @return Property
     */
    public function setAirbnbCalendarUrl(string $airbnbCalendarUrl): Property
    {
        $this->airbnbCalendarUrl = $airbnbCalendarUrl;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getAccessFormUrl(): ?string
    {
        return $this->accessFormUrl;
    }

    /**
     * @param string $accessFormUrl
     * @return Property
     */
    public function setAccessFormUrl(string $accessFormUrl): Property
    {
        $this->accessFormUrl = $accessFormUrl;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastSuccessfulAirbnbSync(): ?\DateTime
    {
        return $this->lastSuccessfulAirbnbSync;
    }

    /**
     * @param DateTime $date
     */
    public function setLastSuccessfulAirbnSync(\DateTime $date = null)
    {
        $this->lastSuccessfulAirbnbSync = $date;
    }

    /**
     * @return string
     */
    public function getLastAirbnbSyncLog(): ?string
    {
        return $this->lastAirbnbSyncLog;
    }

    /**
     * @param string $lastAirbnbSyncLog
     */
    public function setLastAirbnbSyncLog(string $lastAirbnbSyncLog): Property
    {
        $this->lastAirbnbSyncLog = $lastAirbnbSyncLog;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getManagedPeriods(): Collection
    {
        return $this->managedPeriods;
    }

    /**
     * @param Collection $managedPeriods
     * @return Property
     */
    public function setManagedPeriods(Collection $managedPeriods): Property
    {
        $this->managedPeriods = $managedPeriods;
        return $this;
    }

    /**
     * @param DateTime $start
     * @param DateTime|null $end
     * @return Property
     */
    public function addManagedPeriod(DateTime $start, DateTime $end = null): Property
    {
        $this->getManagedPeriods()->add(new TimePeriod($start, $end));
        return $this;
    }

    /**
     * @return boolean
     */
    public function isManagedOn(DateTime $date): bool
    {
        foreach($this->getManagedPeriods() as $period) {
            if($period->contains($date)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper
     *
     * @return bool
     */
    public function isCurrentlyManaged(): bool {
        return $this->isManagedOn(new DateTime);
    }

    /**
     * @return Collection
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    /**
     * @param Collection $reservations
     * @return Property
     */
    public function setReservations(Collection $reservations)
    {
        $this->reservations = $reservations;
        return $this;
    }

    /**
     * @return int
     */
    public function getWehostPercentage(): ?int
    {
        return $this->wehostPercentage;
    }

    /**
     * @param int $wehostPercentage
     * @return Property
     */
    public function setWehostPercentage(int $wehostPercentage): Property
    {
        $this->wehostPercentage = $wehostPercentage;
        return $this;
    }

    /**
     * @return string
     */
    public function getCleaningFeeAmount(): ?string
    {
        return $this->cleaningFeeAmount;
    }

    /**
     * @param string $cleaningFeeAmount
     * @return Property
     */
    public function setCleaningFeeAmount(string $cleaningFeeAmount): Property
    {
        $this->cleaningFeeAmount = $cleaningFeeAmount;
        return $this;
    }

    /**
     * @return Money
     */
    public function getCleaningFee(): Money
    {
        return Money::EUR($this->cleaningFeeAmount);
    }

    /**
     * @param Money $money
     * @return Property
     */
    public function setCleaningFee(Money $money): Property
    {
        $this->checkCurrency($money->getCurrency());
        $this->cleaningFeeAmount = $money->getAmount();
        return $this;
    }

    /**
     * @return string
     */
    public function getMinCommissionAmount(): ?string
    {
        return $this->minCommissionAmount;
    }

    /**
     * @param string $minCommissionAmount
     * @return Property
     */
    public function setMinCommissionAmount(string $minCommissionAmount): Property
    {
        $this->minCommissionAmount = $minCommissionAmount;
        return $this;
    }
    
    /**
     * @param Money $money
     * @return Property
     */
    public function setMinCommissionFee(Money $money): Property
    {
        $this->checkCurrency($money->getCurrency());
        $this->minCommissionAmount = $money->getAmount();
        return $this;
    }
    
    /**
     * @return string
     */
    public function getMinReservationAmount(): ?string
    {
        return $this->minReservationAmount;
    }

    /**
     * @param string $minReservationAmount
     * @return Property
     */
    public function setMinReservationAmount(string $minReservationAmount): Property
    {
        $this->minReservationAmount = $minReservationAmount;
        return $this;
    }
    
    /**
     * @param Money $money
     * @return Property
     */
    public function setMinReservationFee(Money $money): Property
    {
        $this->checkCurrency($money->getCurrency());
        $this->minReservationAmount = $money->getAmount();
        return $this;
    }
    
    /**
     * @return Money
     */
    public function getMinReservationMoney(): ?Money
    {
        return Money::EUR($this->minReservationAmount);
    }
    
    
    /**
     * @return string
     */
    public function getDefaultGuestCleaningFeeAmount(): ?string
    {
        return $this->defaultGuestCleaningFeeAmount;
    }

    /**
     * @param string $defaultGuestCleaningFeeAmount
     * @return Property
     */
    public function setDefaultGuestCleaningFeeAmount(string $defaultGuestCleaningFeeAmount): Property
    {
        $this->defaultGuestCleaningFeeAmount = $defaultGuestCleaningFeeAmount;
        return $this;
    }
    
    /**
     * @return Money
     */
    public function getMinCommissionMoney(): ?Money
    {
        return Money::EUR($this->minCommissionAmount);
    }

    /**
     * @return Money
     */
    public function getDefaultGuestCleaningFee(): ?Money
    {
        return Money::EUR($this->defaultGuestCleaningFeeAmount);
    }

    /**
     * @param Money $money
     * @return Property
     */
    public function setDefaultGuestCleaningFee(Money $money): Property
    {
        $this->checkCurrency($money->getCurrency());
        $this->defaultGuestCleaningFeeAmount = $money->getAmount();
        return $this;
    }
    
    /**
     * @return string
     */
    public function getDeepCleaningFeeAmount(): ?string
    {
        return $this->deepCleaningFeeAmount;
    }

    /**
     * @param string $deepCleaningFeeAmount
     * @return Property
     */
    public function setDeepCleaningFeeAmount(string $deepCleaningFeeAmount): Property
    {
        $this->deepCleaningFeeAmount = $deepCleaningFeeAmount;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getBeddingFeeAmount(): ?string
    {
        return $this->beddingFeeAmount;
    }

    /**
     * @param string $beddingFeeAmount
     * @return Property
     */
    public function setBeddingFeeAmount(string $beddingFeeAmount): Property
    {
        $this->beddingFeeAmount = $beddingFeeAmount;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getCleaningFeeAmountWithoutBedding(): ?string
    {
        return $this->cleaningFeeAmountWithoutBedding;
    }

    /**
     * @param string $cleaningFeeAmountWithoutBedding
     * @return Property
     */
    public function setCleaningFeeAmountWithoutBedding(string $cleaningFeeAmountWithoutBedding): Property
    {
        $this->cleaningFeeAmountWithoutBedding = $cleaningFeeAmountWithoutBedding;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getExtraCharges(): Collection
    {
        return $this->extraCharges;
    }

    /**
     * @param Collection $extraCharges
     */
    public function setExtraCharges(Collection $extraCharges)
    {
        $this->extraCharges = $extraCharges;
    }

    /**
     * @return Collection
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    /**
     * @param Collection $invoices
     * @return Property
     */
    public function setInvoices(Collection $invoices): Property
    {
        $this->invoices = $invoices;
        return $this;
    }

    /**
     * @return CleaningPresta
     */
    public function getDefaultCleaningPresta(): ?CleaningPresta
    {
        return $this->defaultCleaningPresta;
    }

    /**
     * @return Property
     */
    public function setDefaultCleaningPresta(?CleaningPresta $defaultCleaningPresta): Property
    {
        $this->defaultCleaningPresta = $defaultCleaningPresta;
        return $this;
    }
    
    public function __toString()
    {
        return "$this->label";
    }

    private function checkCurrency(Currency $currency)
    {
        if($currency->getCode() != "EUR") {
            throw new \LogicException("Only euros are supported right now");
        }
    }

    /**
     * @ORM\PreRemove
     */
    public function removeManagedPeriods()
    {
        $this->managedPeriods->clear();
    }
}
