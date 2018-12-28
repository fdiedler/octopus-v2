<?php

use PHPUnit\Framework\Assert;
use App\Entity\Property;
use App\Entity\Reservation;

/**
 * @author Benoit Del Basso <benoit.delbasso@mylittleparis.com>
 */
trait AirbnbContextTrait
{

    /**
     * @When I sync the property :property
     */
    public function iSyncTheProperty(Property $property)
    {
        $this->get('App\Airbnb\AirbnbBot')->syncProperty($property);
    }

    /**
     * @Then the Airbnb last pricing sync date for reservation :reservation is not null
     */
    public function theLastPricingSyncDateIsNotNull(Reservation $reservation)
    {
        Assert::assertNotNull($reservation->getLastSuccessfulAirbnbPricingSyncDate());
    }

    /**
     * @Then the Airbnb last successful sync date for :property is not null
     */
    public function theLastSuccessfulSyncDateIsNotNull(Property $property)
    {
        Assert::assertNotNull($property->getLastSuccessfulAirbnbSync());
    }

    /**
     * @Then the Airbnb sync log for :property is not empty
     */
    public function theSyncLogIsNotEmpty(Property $property)
    {
        Assert::assertNotEmpty($property->getLastAirbnbSyncLog());
    }

    /**
     * @Given all the reservations pricing details were synced with Airbnb just now
     */
    public function allTheReservationsPricingDetailsWereSyncedJustNow()
    {
        $em = $this->getEntityManager();
        foreach($this->getRepository('App\Entity\Reservation')->findAllWithFilter(null) as $reservation)
        {
            $reservation->setLastSuccessfulAirbnbPricingSyncDate(new DateTime);
            $em->persist($reservation);
        }
        $em->flush();
    }

    /**
     * @Given the reservation :reservation corresponds to Airbnb ID :airbnbId
     */
    public function theReservationCorrespondsToAirbnbId(Reservation $reservation, string $airbnbId)
    {
        $reservation->setAirbnbId($airbnbId);
        $this->persistAndFlush($reservation);
    }

    /**
     * @Given property :property has Airbnb Calendar url :url
     */
    public function propertyHasAirbnbCalendarUrl(Property $property, string $url)
    {
        $property->setAirbnbCalendarUrl($url);
        $this->persistAndFlush($property);
    }

}
