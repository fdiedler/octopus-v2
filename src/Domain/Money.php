<?php

namespace App\Domain;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class Money
{

    private $formatters;

    public function getFormatter($lang = 'en_US')
    {
        if(isset($this->formatters[$lang])) {
            return $this->formatters[$lang];
        }

        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter($lang, \NumberFormatter::CURRENCY);
        $formatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        $this->formatters[$lang] = $formatter;
        return $formatter;
    }

}
