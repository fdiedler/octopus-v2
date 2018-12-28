<?php

namespace App\Domain;

use App\Entity\Reservation;
use App\Entity\Cleaning;
use Money\Money;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class Finance
{

    private $taxRate = 20; // 20%

    /**
     * Returns the turnover for a single reservation.
     */
    public function getTurnoverFromReservation(Reservation $reservation, ?Cleaning $cleaning): MoneyWithTax
    {
        return MoneyWithTax::sum(array_values($this->getSplittedTurnoverFromReservation($reservation, $cleaning)));
    }

    /**
     * Returns the turnover for a single reservation, splitted by its constituents.
     * The keys of the resulting array are: 'hostFee', 'hostCleaningFee', 'extraCharges'.
     */
    public function getSplittedTurnoverFromReservation(Reservation $reservation, ?Cleaning $cleaning): array
    {
        return [
            'hostFee' => $this->hostFee($reservation),
            'hostCleaningFee' => $this->hostCleaningFee($reservation, $cleaning),
        ];
    }

    /**
     * The base amount on which WeHost percentages are applied for a single reservation.
     */
    public function feeBase(Reservation $reservation): Money
    {
        // Check if the host profit is valid
        if ($reservation->getHostProfit() == null)
        {
            return Money::EUR(0);
        }
        
        // Apply the discount if needed
        if ($reservation->getDiscount() != null)
        {
            return $reservation->getHostProfit()->subtract($reservation->getGuestCleaningFee())->subtract($reservation->getDiscount());
        }
    
        return $reservation->getHostProfit()->subtract($reservation->getGuestCleaningFee());
    }
    
    /**
     * The fee that the Host has to pay to WeHost for the given reservation.
     */
    public function hostFee(Reservation $reservation): MoneyWithTax
    {
        $feeBase = $this->feeBase($reservation);

        if ($feeBase < $reservation->getProperty()->getMinReservationMoney())
        {
            return $this->moneyWithDefaultTax($reservation->getProperty()->getMinCommissionMoney());
        }
        else
        {
            $feeWithTaxes = $feeBase->divide(100)->multiply($reservation->getProperty()->getWehostPercentage());
            $taxes = $feeWithTaxes->multiply($this->taxRate)->divide(100+$this->taxRate);

            return new MoneyWithTax(
                $feeWithTaxes->subtract($taxes),
                $taxes
            );
        }
    }

    /**
     * The cleaning costs for a single reservation.
     */
    public function hostCleaningFee(Reservation $reservation, ?Cleaning $cleaning): MoneyWithTax
    {
        if($reservation->getStatus() == Reservation::STATUS_CANCELED) {
            return MoneyWithTax::zero();
        }
        
        if($cleaning == null) {
            return MoneyWithTax::zero();
        }
        
        return $this->moneyWithTax(
            $cleaning->getAmountWithoutTaxesAsMoney(),
            $this->taxRate
        );
    }

    /**
     * The commmission of the market manager for a given reservation
     */
    public function getManagerCommission(Reservation $reservation): Money
    {
        $property = $reservation->getProperty();
        $feeBase = $this->feeBase($reservation);
        
        if ($feeBase < $property->getMinReservationMoney())
        {
            // Use minimum facturation
            return $property->getMinReservationMoney()
                ->multiply($property->getMarket()->getManagerPercentage() ?? 0)
                ->divide(100);
        }
            
        return $this->feeBase($reservation)
            ->multiply($property->getMarket()->getManagerPercentage() ?? 0)
            ->divide(100);
    }

    /**
     * Creates a MoneyWithTax object from an amount without taxes, and a tax rate.
     */
    public function moneyWithTax(Money $withoutTax, int $currentTaxRate)
    {
        return new MoneyWithTax($withoutTax, $withoutTax->multiply($currentTaxRate)->divide(100));
    }

    /**
     * Creates a MoneyWithTax object from an amount without taxes, and the default tax rate.
     */
    public function moneyWithDefaultTax(Money $withoutTax)
    {
        return new MoneyWithTax($withoutTax, $withoutTax->multiply($this->taxRate)->divide(100));
    }
}
