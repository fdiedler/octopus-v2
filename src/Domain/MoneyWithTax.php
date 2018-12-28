<?php

namespace App\Domain;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;

/**
 * A value object that contains two Money elements: an amount without taxes,
 * and the amount of taxes that is associated to it.
 *
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
final class MoneyWithTax
{

    private $withoutTaxes;
    private $taxes;

    public function __construct(Money $withoutTaxes, Money $taxes)
    {
        $this->withoutTaxes = $withoutTaxes;
        $this->taxes = $taxes;
    }

    public function getWithoutTaxes(): Money
    {
        return $this->withoutTaxes;
    }

    public function getTaxes(): Money
    {
        return $this->taxes;
    }

    public function getWithTaxes(): Money
    {
        return $this->withoutTaxes->add($this->taxes);
    }

    public function add(MoneyWithTax $other)
    {
        return new MoneyWithTax(
            $this->withoutTaxes->add($other->withoutTaxes),
            $this->taxes->add($other->taxes)
        );
    }

    public function sub(MoneyWithTax $other)
    {
        return new MoneyWithTax(
            $this->withoutTaxes->sub($other->withoutTaxes),
            $this->taxes->sub($other->taxes)
        );
    }

    public function equals(MoneyWithTax $other)
    {
        return $this->withoutTaxes->equals($other->withoutTaxes) && $this->taxes->equals($other->taxes);
    }

    public function isZero()
    {
        return $this->withoutTaxes->isZero() && $this->taxes->isZero();
    }

    public static function zero(string $currency = 'EUR')
    {
        $zero = new Money(0, new Currency($currency));
        return new MoneyWithTax($zero, $zero);
    }

    public static function sum(array $array, $defaultCurrency = 'EUR'): MoneyWithTax
    {
        return array_reduce(
            $array,
            function(MoneyWithTax $carry, MoneyWithTax $element) { return $carry->add($element); },
            MoneyWithTax::zero($defaultCurrency)
        );
    }

    public function format(MoneyFormatter $formatter)
    {
        return $formatter->format($this->getWithoutTaxes()).' (+ '.$formatter->format($this->getTaxes()).' taxes)';
    }

    public function __toString()
    {
        return $this->format($this->defaultMoneyFormatter());
    }

    private function defaultMoneyFormatter(): MoneyFormatter
    {
        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        return new IntlMoneyFormatter($numberFormatter, $currencies);
    }

}
