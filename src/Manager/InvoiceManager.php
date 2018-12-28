<?php

namespace App\Manager;

use App\Domain\Finance;
use App\Domain\MoneyWithTax;
use App\Entity\Property;
use App\Entity\Cleaning;
use App\Entity\Reservation;
use App\Entity\Invoice;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Doctrine\Common\Persistence\ManagerRegistry;
use DateTime;
use DateInterval;
use App\Security\UserContext;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class InvoiceManager
{

    private $emr;
    private $finance;
    private $session;
    private $userContext;
    
    public function __construct(ManagerRegistry $emr, Finance $finance, SessionInterface $session)
    {
        $this->emr = $emr;
        $this->finance = $finance;
        $this->session = $session;
        $this->userContext = $session->get('userContext');
    }

    /**
     * @param string $reference : Invoice reference to modify
     * @param bool $value : Value
     * @return
     */
    public function setInvoiceStatus(string $reference, bool $value)
    {
        $em = $this->emr->getManager();
        $invoice = $em->getRepository('App\Entity\Invoice')->findByWithFilter(["reference" => $reference], $this->userContext);
        
        if (count($invoice) == 1)
        {
            $invoice[0]->setDone($value);
            $em->persist($invoice[0]);
            $em->flush();
        }
    }
    
    public function generateInvoices(int $year, int $month)
    {
        $em = $this->emr->getManager();

        $monthStart = DateTime::createFromFormat("Y-m-d H:i:s","$year-$month-01 00:00:00");
        $monthEnd = (clone $monthStart)
            ->add(DateInterval::createFromDateString("1 month"))
            ->add(DateInterval::createFromDateString("-1 second"));

        // remove all previous invoices for the same period
        $em->getRepository('App\Entity\Invoice')->deleteAllByDate($monthStart);

        $moneyFormatter = $this->moneyFormatter();

        $allProperties = $this->emr->getRepository(Property::class)->findAllWithFilter(null);
        foreach($allProperties as $property) {
            $total = MoneyWithTax::zero('EUR');
            $lines = [];
            $billable = false;

            $reservations = $em->getRepository('App\Entity\Reservation')
                ->findManagedByPropertyAndPeriod($property, $monthStart, $monthEnd);

            foreach($reservations as $reservation) {
                // Do not bill an unconfirmed reservation 
                if($reservation->getStatus() != Reservation::STATUS_CONFIRMED) {
                    continue;
                }

                $useMinimumCommission = false;
                $billable = true;
                $subLines = $this->computeFeeForReservation($reservation, $useMinimumCommission);
                
                if ($useMinimumCommission)
                {
                    for ($i=0; $i<count($subLines); $i++) {
                        $line = $subLines[$i];
                        
                        list($label, $amount) = $line;
                        
                        // Do not take into account in the total the profit for this reservation
                        if ($i > 0)
                        {
                            $lines[] = [
                                $label,
                                $moneyFormatter->format($amount->getWithoutTaxes()),
                                $moneyFormatter->format($amount->getTaxes())
                            ];
                        
                            $total = $total->add($amount);
                        }
                        else
                        {
                            // Cancel the amount because we are under the minimum for this reservation
                            $amount = MoneyWithTax::zero();
                            
                            $lines[] = [
                                $label,
                                $moneyFormatter->format($amount->getWithoutTaxes()),
                                $moneyFormatter->format($amount->getTaxes())
                            ];
                        
                            // Take the minimum commission 
                            $amount = $this->finance->moneyWithDefaultTax($property->getMinCommissionMoney());
                            $total = $total->add($amount);
                            
                            // Add a line explaining this
                            $lines[] = [
                                "Réservation ".$this->dateFormatter()->format($reservation->getCheckinDate())." : Facturation minimum",
                                $moneyFormatter->format($amount->getWithoutTaxes()),
                                $moneyFormatter->format($amount->getTaxes())
                            ];
                        }
                    }
                }
                else
                {
                    foreach($subLines as $line) {
                        list($label, $amount) = $line;
                        $total = $total->add($amount);
                        $lines[] = [
                            $label,
                            $moneyFormatter->format($amount->getWithoutTaxes()),
                            $moneyFormatter->format($amount->getTaxes())
                        ];
                    }
                }
            }

            // and extra charges
            foreach($property->getExtraCharges() as $extra) {

                // keep only charges that are billed on the given month
                $billingDate = $extra->getDate();
                if($billingDate == null || $billingDate < $monthStart || $billingDate > $monthEnd) {
                    continue;
                }

                $billable = true;
                $total = $total->add($extra->getAmountWithTaxes());
                $lines[] = [
                    $extra->getLabel(),
                    $moneyFormatter->format($extra->getAmountWithoutTaxesAsMoney()),
                    $moneyFormatter->format($extra->getTaxesAmountAsMoney()),
                ];
            }

            // Add extra cleaning
            $extraCleanings = $em->getRepository('App\Entity\Cleaning')->findByWithFilter(["property" => $property->getId(), "reservation" => null], null);
            foreach($extraCleanings as $extraCleaning)
            {
                // keep only cleaning charges that are billed on the given month
                $billingDate = $extraCleaning->getDate();
                if($billingDate == null || $billingDate < $monthStart || $billingDate > $monthEnd) {
                    continue;
                }
                
                $billable = true;
                $amountWithTaxes = $this->finance->moneyWithDefaultTax($extraCleaning->getAmountWithoutTaxesAsMoney());
                $total = $total->add($amountWithTaxes);
                $lines[] = [
                    $extraCleaning->getCorrelType()." du ".$this->dateFormatter()->format($extraCleaning->getDate()),
                    $moneyFormatter->format($amountWithTaxes->getWithoutTaxes()),
                    $moneyFormatter->format($amountWithTaxes->getTaxes()),
                ];
            }

            if(! $billable) continue; // no invoice if nothing is billable this month

            $invoice = new Invoice("$year-$month-".$property->getId(), $property);
            $invoice->setBillingDate($monthStart);
            $invoice->setAmountWithoutTaxes($total->getWithoutTaxes());
            $invoice->setTaxesAmount($total->getTaxes());
            $invoice->setDetails($lines);
            
            $em->persist($invoice);
        }
        $em->flush();
    }

    /**
     * @param Reservation $reservation
     * @return array an array with 2 columns per line: label (string), amount (MoneyWithTax)
     *         by reference, return a boolean indicating if we should use the minimum commission for this reservation
     */
    protected function computeFeeForReservation(Reservation $reservation, bool &$useMinimumCommission): array
    {
        $property = $reservation->getProperty();

        $dateFormatter = $this->dateFormatter();
        $moneyFormatter = $this->moneyFormatter();

        $label = "Réservation ".$dateFormatter->format($reservation->getCheckinDate());

        // if pricing details were not collected yet, add a line explaining it
        if($reservation->getHostProfit() == null || $reservation->getHostProfitAmount() == "0" || $reservation->getGuestCleaningFee() == null) {
            return [[ "$label -- pas de données prix", MoneyWithTax::zero()]];
        }

        // first take the percentage of the profit
        $feeBase = $this->finance->feeBase($reservation);
        $hostFee = $this->finance->hostFee($reservation);
        $lines[] = [
            sprintf("%s : %s%% de %s ttc (versement reçu %s%s - frais de ménage voyageur %s)",
                $label,
                $property->getWehostPercentage(),
                $moneyFormatter->format($feeBase),
                $moneyFormatter->format($reservation->getHostProfit()),
                ($reservation->getDiscount() != null ? (" - réduction ".$moneyFormatter->format($reservation->getDiscount())) : ""),
                $moneyFormatter->format($reservation->getGuestCleaningFee())
                ),
            $hostFee
        ];
        
        // Indicates if the host profit for this reservation is under the threshold defined for this property
        $useMinimumCommission = false;
        if ($feeBase < $reservation->getProperty()->getMinReservationMoney())
        {
            $useMinimumCommission = true;
        }
        
        // Get the cleaning amount associated to this reservation 
        $cleanings = $reservation->getCleanings();
        if (count($cleanings) == 0)
        {
            $lines[] = [ "$label -- pas de données ménage", MoneyWithTax::zero()];
        }
        else
        {
            // then add cleaning costs
            foreach ($cleanings as $cleaning)
            {
                $cleaningDate = $dateFormatter->format($cleaning->getDate());
                $hostCleaningFee = $this->finance->hostCleaningFee($reservation, $cleaning);
                if ($hostCleaningFee == MoneyWithTax::zero())
                {
                    $lines[] = [
                        "$label : Pas de ménage associé",
                        $hostCleaningFee
                    ];
                }
                else 
                {
                    $lines[] = [
                        "$label : Ménage + Blanchisserie + Linge de Maison (effectué le $cleaningDate)",
                        $hostCleaningFee
                    ];
                }
            }
        }
        
        return $lines ;
    }

    public function moneyFormatter(): IntlMoneyFormatter
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        return new IntlMoneyFormatter($numberFormatter, $currencies);
    }

    private function dateFormatter(): \IntlDateFormatter
    {
        return new \IntlDateFormatter(
            "fr_FR",
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Paris',
            \IntlDateFormatter::GREGORIAN,
            'd MMMM'
        );
    }


}
