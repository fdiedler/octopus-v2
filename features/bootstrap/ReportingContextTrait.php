<?php

use PHPUnit\Framework\Assert;
use App\Entity\Market;
use App\Domain\MoneyWithTax;

/**
 * @author Benoit Del Basso <benoit.delbasso@mylittleparis.com>
 */
trait ReportingContextTrait
{

    /**
     * @Then the turnover between :start and :end for market :market is :amount with :taxes taxes
     */
    public function theTurnoverBetweenStartAndEndForMarketIs(DateTime $start, DateTime $end, Market $market, string $amount, string $taxes)
    {
        $this->theValueForMarketBetweenStartAndEndIs('turnover', $market, $start, $end, $amount, $taxes);
    }

    /**
     * @Then the total cleaning costs between :start and :end for market :market are :amount with :taxes taxes
     */
    public function theTotalCleaningCostsBetweenStartAndEndForMarketIs(DateTime $start, DateTime $end, Market $market, string $amount, string $taxes)
    {
        $this->theValueForMarketBetweenStartAndEndIs('cleaningCosts', $market, $start, $end, $amount, $taxes);
    }

    /**
     * @Then the number of billable hosts between :start and :end for market :market is :number
     */
    public function theNumberOfBillableHostsBetweenStartAndEndForMarketIs(DateTime $start, DateTime $end, Market $market, int $number)
    {
        $reportingByMarket = $this->get('App\Domain\ReportingByMarket');
        $reporting = $reportingByMarket->getReportingByMarketForPeriod($start, $end);
        Assert::assertEquals($number, $reporting[$market->getName()]['billableHosts']);
    }

    /**
     * @Then the commission of the manager of :market between :start and :end is :amount
     */
    public function theCommissionOfTheManagerOfMarketBetweenStartAndEndIs(Market $market, DateTime $start, DateTime $end, string $amount)
    {
        $this->theValueForMarketBetweenStartAndEndIs('managerCommission', $market, $start, $end, $amount);
    }

    private function theValueForMarketBetweenStartAndEndIs(string $value, Market $market, DateTime $start, DateTime $end, string $amount, string $expectedTaxes = null)
    {
        $reportingByMarket = $this->get('App\Domain\ReportingByMarket');
        $reporting = $reportingByMarket->getReportingByMarketForPeriod($start, $end);
        $actual = $reporting[$market->getName()][$value];
        Assert::assertEquals($this->moneyParser()->parse($amount), $actual instanceof MoneyWithTax ? $actual->getWithoutTaxes() : $actual);
        if(null !== $expectedTaxes) {
            if(! ($actual instanceof MoneyWithTax)) {
                throw new \Exception("Need a MoneyWithTax to assert taxes");
            }
            Assert::assertEquals($this->moneyParser()->parse($expectedTaxes), $actual->getTaxes());
        }
    }
}
