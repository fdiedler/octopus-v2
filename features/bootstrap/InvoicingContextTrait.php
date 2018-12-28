<?php

use PHPUnit\Framework\Assert;
use App\Entity\Invoice;
use App\Entity\Property;
use Behat\Gherkin\Node\TableNode;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\IntlMoneyParser;

/**
 * @author Benoit Del Basso <benoit.delbasso@mylittleparis.com>
 */
trait InvoicingContextTrait
{

    /**
     * @Transform :invoice
     */
    public function fixInvoice($invoice)
    {
        return $this->find(Invoice::class, $invoice, "reference");
    }

    /**
     * @When I generate invoices for :month
     */
    public function iGenerateInvoicesFor(string $month)
    {
        $date = DateTime::createFromFormat('F Y', $month);
        $this->get('App\Manager\InvoiceManager')->generateInvoices($date->format('Y'), $date->format('n'));
    }

    /**
     * @Then an invoice :invoice of :amountWithoutTaxes and additional :taxesAmount taxes should exist for property :property for :month with details:
     */
    public function anInvoiceOfIncludingTaxesShouldExistForPropertyFor(Invoice $invoice, string $amount, string $taxesAmount, Property $property, string $month, TableNode $details)
    {
        Assert::assertEquals($property->getId(), $invoice->getProperty()->getId(), "Property mismatch");
        Assert::assertEquals($month, $invoice->getBillingDate()->format('F Y'));
        Assert::assertEquals($details->getRows(), $invoice->getDetails(), "Got: ".print_r($invoice->getDetails(),true));
        Assert::assertEquals($amount, $this->moneyFormatter()->format($invoice->getAmountWithoutTaxes()));
        Assert::assertEquals($taxesAmount, $this->moneyFormatter()->format($invoice->getTaxesAmount()));
    }

    /**
     * @Then property :property should have no invoice for :month
     */
    public function propertyShouldHaveNoInvoiceFor(Property $property, string $month)
    {
        foreach($property->getInvoices() as $invoice) {
            Assert::assertNotEquals($month, $invoice->getBillingDate()->format('F Y'));
        }
    }

    public function moneyParser(): IntlMoneyParser
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        return new IntlMoneyParser($numberFormatter, $currencies);
    }

    public function moneyFormatter(): IntlMoneyFormatter
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        return new IntlMoneyFormatter($numberFormatter, $currencies);
    }

}
