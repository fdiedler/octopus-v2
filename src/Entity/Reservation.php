<?php namespace App\Entity;

use Money\Money;
use Money\Currency;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * A reservation (booking of a listed appartment for a period by a guest)
 *
 * @ORM\Entity(repositoryClass="App\Repository\ReservationRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="reservation", indexes={
 *     @ORM\Index(name="checkinDate", columns={"checkin_date"})
 * })
 */
class Reservation
{

    const STATUS_PENDING = "pending";
    const STATUS_CONFIRMED = "confirmed";
    const STATUS_CANCELED = "canceled";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * WeHost reference number
     *
     * @ORM\Column(type="string", length=64)
     * @var string
     */
    private $reference;

    /**
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="reservations")
     * @var Property
     */
    private $property;

    /**
     * ID of this entity on the Airbnb platform
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     * @var string
     */
    private $airbnbId;

    /**
     * Check-in date
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $checkinDate;

    /**
     * Check-out date
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $checkoutDate;

    /**
     * Name of the guest
     *
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $guestName;

    /**
     * Phone number of the guest
     *
     * @ORM\Column(type="string", length=30)
     * @var string
     */
    private $guestPhoneNumber;

    /**
     * Status of the reservation
     *
     * @ORM\Column(type="string", length=20)
     * @var string
     */
    private $status;

    /**
     * Amount of money that the host actually got from the platform (Airbnb)
     * In euros, cents
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     * @var string
     */
    private $hostProfitAmount;
    
    /**
     * Discount amount got from the platform (Airbnb)
     * In euros, cents
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     * @var string
     */
    private $discountAmount;

    /**
     * Amount of money that was charged to the guest for cleaning
     * (in euros, cents)
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     * @var string
     */
    private $guestCleaningFeeAmount;

    /**
     * Date of last synchronization of the pricing details (host profit, guest cleaning fee)
     * with Airbnb.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    private $lastSuccessfulAirbnbPricingSyncDate;
    
    /**
     * All the cleanings of this reservation
     *
     * @ORM\OneToMany(targetEntity="Cleaning", mappedBy="reservation", cascade={"remove"})
     * @var Collection
     */
    private $cleanings;
    
    /**
     * The checkin associated to this reservation
     *
     * @ORM\OneToMany(targetEntity="Checkin", mappedBy="reservation", cascade={"remove"})
     * @var Collection
     */
    private $checkins;

    // Virtual property (not exists in Doctrine)
    public $canceled;
    
    public function __construct(string $reference = "", Property $property = null, DateTime $checkinDate = null, DateTime $checkoutDate = null)
    {
        // Allow to add reservation if they are managed for another platform (like Booking or Abritel...)
        // In this case, $reference is empty
        $this->reference = $reference;
        $this->status = ($reference == "" ? self::STATUS_CONFIRMED : self::STATUS_PENDING);
        $this->property = $property;
        $this->checkinDate = ($reference == "" ? new DateTime("now") : $checkinDate);
        $this->checkoutDate = ($reference == "" ? new DateTime("now") : $checkoutDate);
        $this->cleanings = new ArrayCollection();
        $this->checkins = new ArrayCollection();
        $this->canceled = false;
        $this->guestPhoneNumber = "";
        $this->guestName = "";
        $this->airbnbId = "";
    }
    
    /**
     * @return boolean
     */
    public function isCanceled(): bool
    {
        return ($this->getStatus() == Reservation::STATUS_CANCELED);
    }
    
    /**
     * @return Reservation
     */
    public function setCanceled(bool $value) : Reservation
    {
        $this->canceled = $value;
        
        if ($value === true)
            $this->setStatus(Reservation::STATUS_CANCELED);
        else 
            $this->setStatus(Reservation::STATUS_CONFIRMED);
        
        return $this;
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
     * @return Property
     */
    public function getProperty(): ?Property
    {
        return $this->property;
    }
    
    /**
     * @return Reservation
     */
    public function setProperty(Property $property): Reservation
    {
        $this->property = $property;
        return $this;
    }

    /**
     * A reservation is managed if its checkin date is within the managed
     * periods of the corresponding property.
     *
     * @return boolean
     */
    public function isManaged(): bool
    {
        return $this->property->isManagedOn($this->checkinDate);
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
     * @return Reservation
     */
    public function setAirbnbId(string $airbnbId): Reservation
    {
        $this->airbnbId = $airbnbId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCheckinDate(): DateTime
    {
        return $this->checkinDate;
    }

    /**
     * @param DateTime $checkinDate
     * @return Reservation
     */
    public function setCheckinDate(DateTime $checkinDate): Reservation
    {
        $this->checkinDate = $checkinDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCheckoutDate(): DateTime
    {
        return $this->checkoutDate;
    }

    /**
     * @param DateTime $checkoutDate
     * @return Reservation
     */
    public function setCheckoutDate(DateTime $checkoutDate): Reservation
    {
        if($this->checkinDate >= $checkoutDate) {
            throw new \LogicException("Check-out cannot be before check-in");
        }
        $this->checkoutDate = $checkoutDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuestName(): string
    {
        return $this->guestName;
    }

    /**
     * @param string $guestName
     * @return Reservation
     */
    public function setGuestName(string $guestName): Reservation
    {
        $this->guestName = $guestName;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuestPhoneNumber(): string
    {
        return $this->guestPhoneNumber;
    }

    /**
     * @param string $guestPhoneNumber
     * @return Reservation
     */
    public function setGuestPhoneNumber(string $guestPhoneNumber): Reservation
    {
        $this->guestPhoneNumber = $guestPhoneNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Reservation
     */
    public function setStatus(string $status): Reservation
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getHostProfitAmount(): ?string
    {
        return $this->hostProfitAmount;
    }

    /**
     * @param mixed $hostProfitAmount
     */
    public function setHostProfitAmount($hostProfitAmount): Reservation
    {
        $this->hostProfitAmount = $hostProfitAmount;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    /**
     * @param mixed $discountAmount
     */
    public function setDiscountAmount($discountAmount): Reservation
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuestCleaningFeeAmount(): ?string
    {
        return $this->guestCleaningFeeAmount;
    }

    /**
     * @param string $guestCleaningFeeAmount
     */
    public function setGuestCleaningFeeAmount(string $guestCleaningFeeAmount): Reservation
    {
        $this->guestCleaningFeeAmount = $guestCleaningFeeAmount;
        return $this;
    }

    /**
     * @return Money
     */
    public function getHostProfit(): ?Money
    {
        return null == $this->hostProfitAmount ?
            null :
            Money::EUR($this->hostProfitAmount);
    }

    /**
     * @param Money $hostProfit
     * @return Reservation
     */
    public function setHostProfit(Money $hostProfit): Reservation
    {
        $this->checkCurrency($hostProfit->getCurrency());
        $this->hostProfitAmount = $hostProfit->getAmount();
        return $this;
    }
    
    /**
     * @return Money
     */
    public function getDiscount(): ?Money
    {
        return null == $this->discountAmount ?
            null :
            Money::EUR($this->discountAmount);
    }

    /**
     * @param Money $discountAmount
     * @return Reservation
     */
    public function setDiscount(Money $discountAmount): Reservation
    {
        $this->checkCurrency($discountAmount->getCurrency());
        $this->discountAmount = $discountAmount->getAmount();
        return $this;
    }

    /**
     * @return Money
     */
    public function getGuestCleaningFee(): ?Money
    {
        return null == $this->guestCleaningFeeAmount ?
            null :
            Money::EUR($this->guestCleaningFeeAmount);
    }

    /**
     * @param Money $guestCleaningFee
     * @return Reservation
     */
    public function setGuestCleaningFee(Money $guestCleaningFee): Reservation
    {
        $this->checkCurrency($guestCleaningFee->getCurrency());
        $this->guestCleaningFeeAmount = $guestCleaningFee->getAmount();
        return $this;
    }

    public function clearPricingDetails() {
        $this->hostProfitAmount = null;
        $this->guestCleaningFeeAmount = null; 
        $this->discountAmount = null;
    }

    /**
     * @return DateTime
     */
    public function getLastSuccessfulAirbnbPricingSyncDate(): ?DateTime
    {
        return $this->lastSuccessfulAirbnbPricingSyncDate;
    }

    /**
     * @param DateTime $lastSuccessfulAirbnbPricingSyncDate
     */
    public function setLastSuccessfulAirbnbPricingSyncDate(?DateTime $date): Reservation
    {
        $this->lastSuccessfulAirbnbPricingSyncDate = $date;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getCleanings(): Collection
    {
        return $this->cleanings;
    }

    /**
     * @param Collection $cleanings
     */
    public function setCleanings(Collection $cleanings): Reservation
    {
        $this->cleanings = $cleanings;
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getCheckins(): Collection
    {
        return $this->checkins;
    }

    /**
     * @param Collection $checkins
     */
    public function setCheckins(Collection $checkins): Reservation
    {
        $this->checkins = $checkins;
        return $this;
    }
    
    public function __toString()
    {
        return $this->getAirbnbId();
    }

    private function checkCurrency(Currency $currency)
    {
        if($currency->getCode() != "EUR") {
            throw new \LogicException("Only euros are supported right now");
        }
    }

	 /**
	 *
	 * @ORM\PrePersist
	 * @ORM\PreUpdate
	 */
	public function updatedTimestamps()
	{
		//$this->setLastSuccessfulAirbnbPricingSyncDate(new \DateTime('now'));
	}
}
