<?php

use App\Entity\User;
use App\Entity\Market;
use App\Entity\Host;
use App\Entity\Property;
use App\Entity\Reservation;
use App\Entity\ExtraCharge;
use App\Entity\Cleaning;
use App\Entity\CleaningPresta;
use Money\Money;
use PHPUnit\Framework\Assert;

/**
 * @author Benoit Del Basso <benoit.delbasso@mylittleparis.com>
 */
trait HostingContextTrait
{

    /**
     * @Transform :market
     */
    public function fixMarket($market)
    {
        return $this->find(Market::class, $market, "name");
    }

    /**
     * @Transform :host
     */
    public function fixHost($host)
    {
        return $this->find(Host::class, $host, "name");
    }

    /**
     * @Transform :property
     */
    public function fixProperty($property)
    {
        return $this->find(Property::class, $property, "label");
    }

    /**
     * @Transform :reservation
     */
    public function fixReservation($reservation)
    {
        return $this->find(Reservation::class, $reservation, "reference");
    }

    /**
     * @Given I have a market :name
     */
    public function iHaveAMarket(string $name): Market
    {
        $market = new Market();
        $market->setName($name);
        return $this->persistAndFlush($market);
    }

    /**
     * @Given I have a host :name with email :email
     */
    public function iHaveAHostWithEmail(string $name, string $email): Host
    {
        $host = new Host();
        $host->setName($name);
        $host->setEmail($email);
        $host->setWehostEmailAlias(strtolower($name)."@wehost.fr");
        $host->setAirbnbPassword("test");
        return $this->persistAndFlush($host);
    }

    /**
     * @Given I have a property :reference for host :host and market :market
     */
    public function iHaveAPropertyForHost($reference, Host $host, Market $market): Property
    {
        $property = new Property($host, $market);
        $property->setLabel($reference);
        $property->setWehostPercentage(20);
        $property->setCleaningFee(Money::EUR(3300));
        $property->setDefaultGuestCleaningFee(Money::EUR(0));
        $property->setMinReservationFee(Money::EUR(10000));
        $property->setMinCommissionFee(Money::EUR(20000));
        $property->setType(0); // Default type contract = We Host
        return $this->persistAndFlush($property);
    }

    /**
     * @Given I have a cleaning provider with name :name in market :market
     */
    public function iHaveACleaningProvider(string $name, Market $market): CleaningPresta
    {
        $cleaningPresta = new CleaningPresta();
        $cleaningPresta->setName($name);
        $cleaningPresta->setMarket($market);
        return $this->persistAndFlush($cleaningPresta);
    }
    
    /**
     * @Given the contract for :property has a :percentage percent commission with a cleaning fee of :cleaningFee without taxes
     */
    public function thePropertyHasAnAgreement(Property $property, string $weHostPercentage, string $cleaningFee): Property
    {
        $property->setWehostPercentage(intval($weHostPercentage));
        $property->setCleaningFee($this->moneyParser()->parse($cleaningFee));
        return $this->persistAndFlush($property);
    }

    /**
     * @Given the property :property is being managed between :start and :end
     */
    public function propertyIsBeingManagedBetween(Property $property, DateTime $start, DateTime $end)
    {
        $property->addManagedPeriod($start, $end);
        $this->persistAndFlush($property);
    }

    /**
     * @Given the property :property has a default guest cleaning fee of :amount
     */
    public function thePropertyHasADefaultGuestCleaningFeeOf(Property $property, string $amount)
    {
        $property->setDefaultGuestCleaningFee($this->moneyParser()->parse($amount));
        $this->persistAndFlush($property);
    }
    
    /**
     * @Given the property :property has a cleaning of :amountWithoutTaxes schedulted the :cleaningDate entitled :label
     */
    public function thePropertyHasACleaningOfSchedultedEntitledAnd(Property $property, $amountWithoutTaxes, $cleaningDate, $label): Cleaning
    {
        $cleaning = new Cleaning();
        $cleaning->setAmountWithoutTaxesAsMoney($this->moneyParser()->parse($amountWithoutTaxes));
        $cleaning->setType(0); // Default cleaning type
        $cleaning->setDate(DateTime::createFromFormat('Y-m-d', $cleaningDate));
        $cleaning->setProperty($property);
        $cleaning->setComments($label);
        return $this->persistAndFlush($cleaning);
    }

    /**
     * @Given property :property corresponds to Airbnb ID :listingId
     */
    public function propertyCorrespondsToAirbnbListing(Property $property, string $listingId): Property
    {
        $property->setAirbnbId($listingId);
        return $this->persistAndFlush($property);
    }

    /**
     * @Given the property :property has a :status reservation :reference between :checking and :checkout
     */
    public function thePropertyHasAReservationBetweenAnd(Property $property, $status, $reference, $checkin, $checkout): Reservation
    {
        $reservation = new Reservation(
            $reference,
            $property,
            DateTime::createFromFormat('Y-m-d', $checkin),
            DateTime::createFromFormat('Y-m-d', $checkout));
        $reservation->setStatus($status);
        $reservation->setGuestName("John Doe");
        $reservation->setGuestPhoneNumber("+33 123456789");
        return $this->persistAndFlush($reservation);
    }
    
    /**
     * @Given the reservation :reservation has a cleaning of :amountWithoutTaxes schedulted the :cleaningDate
     */
    public function theReservationHasACleaningOfSchedultedTheEntitledAnd(Reservation $reservation, $amountWithoutTaxes, $cleaningDate): Cleaning
    {
        $cleaning = new Cleaning();
        $cleaning->setAmountWithoutTaxesAsMoney($this->moneyParser()->parse($amountWithoutTaxes));
        $cleaning->setType(0); // Default cleaning type
        $cleaning->setDate(DateTime::createFromFormat('Y-m-d', $cleaningDate));
        $cleaning->setReservation($reservation);
        $cleaning->setProperty($reservation->getProperty());
        return $this->persistAndFlush($cleaning);
    }
    
    /**
     * @Given the cleaning :cleaning provider is :provider
     */
    public function theCleaningProviderIs(Cleaning $cleaning, CleaningPresta $provider): void
    {
        $cleaning->setPresta($provider);
    }

    /**
     * @Given the reservation :reservation brought the host a profit of :hostProfit while guest paid a cleaning fee of :guestCleaningFee
     */
    public function theReservationBroughtTheHostAProfitOfWhileGuestPaidACleaningFeeOf(Reservation $reservation, string $hostProfit, string $guestCleaningFee): void
    {
        $reservation->setHostProfit($this->moneyParser()->parse($hostProfit));
        $reservation->setGuestCleaningFee($this->moneyParser()->parse($guestCleaningFee));
        $this->persistAndFlush($reservation);
    }
    
    /**
     * @Given the reservation :reservation has a discount of :discount
     */
    public function theReservationHasADiscountOf(Reservation $reservation, string $discount): void
    {
        $reservation->setDiscount($this->moneyParser()->parse($discount));
        $this->persistAndFlush($reservation);
    }

    /**
     * @Given the property :property has an extra charge of :amountWithoutTaxes with :taxesAmount taxes on :date for :label
     */
    public function theReservationHasAnExtraCharge(Property $property, string $amountWithoutTaxes, string $taxesAmount, \DateTime $date, string $label)
    {
        $extra = new ExtraCharge();
        $extra->setProperty($property);
        $extra->setDate($date);
        $extra->setAmountWithoutTaxesAsMoney($this->moneyParser()->parse($amountWithoutTaxes));
        $extra->setTaxesAmountAsMoney($this->moneyParser()->parse($taxesAmount));
        $extra->setLabel($label);
        $this->persistAndFlush($extra);
    }

    /**
     * @Then the property :property should have :count reservations
     */
    public function thePropertyShouldHaveReservations(Property $property, int $count)
    {
        Assert::assertEquals($count, $property->getReservations()->count());
    }

    /**
     * @Then the reservation :reservation should exist for :property with status :status, checkin on :checkinDate and checkout on :checkoutDate
     */
    public function theReservationShouldExistForPropertyWithCheckinOnAndCheckoutOn(
        Reservation $reservation, Property $property, string $status, string $checkinDate, string $checkoutDate)
    {
        Assert::assertEquals($property, $reservation->getProperty());
        Assert::assertEquals($status, $reservation->getStatus());
        Assert::assertEquals($checkinDate, $reservation->getCheckinDate()->format('Y-m-d'));
        Assert::assertEquals($checkoutDate, $reservation->getCheckoutDate()->format('Y-m-d'));
    }

    /**
     * @Then the reservation :reservation should have a guest named :guestName with phone number :guestPhoneNumber
     */
    public function theReservationShouldHaveAGuestNamedWithPhoneNumber(Reservation $reservation, string $guestName, string $guestPhoneNumber)
    {
        Assert::assertEquals($guestName, $reservation->getGuestName());
        Assert::assertEquals($guestPhoneNumber, $reservation->getGuestPhoneNumber());
    }

    /**
     * @Then the reservation :reservation should bring the host a profit of :hostProfit while guest pays a cleaning fee of :cleaningFee
     */
    public function theReservationShouldBringTheHostAProfitAndTheGuestPaysACleaningFee(Reservation $reservation, string $hostProfit, string $guestCleaningFee)
    {
        Assert::assertEquals($this->moneyParser()->parse($hostProfit), $reservation->getHostProfit());
        Assert::assertEquals($this->moneyParser()->parse($guestCleaningFee), $reservation->getGuestCleaningFee());
    }

    /**
     * @Then the reservation :reservation should bring a discount of :discount
     */
    public function theReservationShouldBringADiscount(Reservation $reservation, string $discount)
    {
        Assert::assertEquals($this->moneyParser()->parse($discount), $reservation->getDiscount());
    }
    
    /**
     * @Then the reservation :reservation should not have any discount
     */
    public function theReservationShouldNotHaveAnyDiscount(Reservation $reservation)
    {
        Assert::assertEquals(null, $reservation->getDiscount());
    }
    
    /**
     * @Then User :user should managed :nbCleaningPresta cleaning providers
     */
    public function weShouldHaveCleaningProviders(User $user, int $nbCleaningPresta)
    {
        $userContext = $this->getUserContext($user->getEmail());
        
        $cleaningPresta = $this->getRepository(CleaningPresta::class)->findAllWithFilter($userContext);
        
        Assert::assertEquals(count($cleaningPresta), $nbCleaningPresta);
    }
    
    /**
     * @Then User :user should managed :nbHost hosts and :nbProp properties
     */
    public function weShouldHaveProperties(User $user, int $nbHost, int $nbProp)
    {
        $userContext = $this->getUserContext($user->getEmail());
        
        $hosts = $this->getRepository(Host::class)->findAllWithFilter($userContext);
        $properties = $this->getRepository(Property::class)->findAllWithFilter($userContext);
        
        Assert::assertEquals(count($hosts), $nbHost);
        Assert::assertEquals(count($properties), $nbProp);
    }
    
    /**
     * @Then User :user should managed :nbResa reservations and :nbCleaning cleanings with :nbExtracharges extracharges
     */
    public function weShouldManagedReservationAndCleaning(User $user, int $nbResa, int $nbCleaning, int $nbExtracharges)
    {
        $userContext = $this->getUserContext($user->getEmail());
        
        $reservations = $this->getRepository(Reservation::class)->findAllWithFilter($userContext);
        $cleanings = $this->getRepository(Cleaning::class)->findAllWithFilter($userContext);
        $extracharges = $this->getRepository(ExtraCharge::class)->findAllWithFilter($userContext);
        
        Assert::assertEquals(count($reservations), $nbResa);
        Assert::assertEquals(count($cleanings), $nbCleaning);
        Assert::assertEquals(count($extracharges), $nbExtracharges);
    }
    
    /**
     * @Then User :user should managed :nbResa confirmed reservations between :beginDate and :endDate
     */
    public function weShouldManagedConfirmedReservationBetween(User $user, int $nbResa, string $beginDate, string $endDate)
    {
        $userContext = $this->getUserContext($user->getEmail());
        
        $reservations = $this->getRepository(Reservation::class)->findManagedByPeriodAndStatuses(
            DateTime::createFromFormat('Y-m-d', $beginDate), 
            DateTime::createFromFormat('Y-m-d', $endDate),
            [Reservation::STATUS_CONFIRMED],
            $userContext
        );
        
        Assert::assertEquals(count($reservations), $nbResa);
    }
}
