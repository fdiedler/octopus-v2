<?php

use PHPUnit\Framework\Assert;
use App\Entity\Property;
use App\Entity\Reservation;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
trait AirbnbResaContextTrait
{

    /**
     * @When I sync the reservation price :reservation
     */
    public function iSyncTheReservationPriceOf(Reservation $reservation)
    {
        $this->get('App\Airbnb\AirbnbReservationBot')->syncOneReservationPrice($reservation);
    }
}
