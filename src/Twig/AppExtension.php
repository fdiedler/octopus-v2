<?php

namespace App\Twig;

use App\Domain\MoneyWithTax;
use Money\Money;
use App\Domain\Money as MoneyDomain;

class AppExtension extends \Twig_Extension
{

    private $env;
    private $moneyFormatter;

    public function __construct(string $env, MoneyDomain $moneyDomain)
    {
        $this->env = $env;
        $this->moneyFormatter = $moneyDomain->getFormatter();
    }

    public function getFilters()
    {
        return array(
            new \Twig_Filter('formatMoney', [ $this, 'formatMoney' ]),
            new \Twig_Filter('formatDate', [ $this, 'formatDate' ]),
            new \Twig_Filter('formatTime', [ $this, 'formatTime' ]),
        );
    }

    public function formatTime($time)
    {
        return $time->format("H:i");
    }
    
    public function formatDate($date, $full = false)
    {
        $format = ($full ? 'EEEE d MMMM' : 'd MMMM');
        
        $dateFormatter = new \IntlDateFormatter(
            "fr_FR",
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            'Europe/Paris',
            \IntlDateFormatter::GREGORIAN,
            $format
        );
       
        if (is_string($date))
        {
            $date = new \DateTime($date);
        }
            
        return ucfirst($dateFormatter->format($date));
    }
    
    public function formatMoney($money)
    {
        if($money instanceof Money) {
            return $this->moneyFormatter->format($money);
        } elseif($money instanceof MoneyWithTax) {
            return $this->moneyFormatter->format($money->getWithoutTaxes())
            .' (and '. $this->moneyFormatter->format($money->getTaxes()).' taxes)';
        } elseif(is_string($money)) {
            return $this->moneyFormatter->format(Money::EUR($money));
        } else {
            throw new \Exception("Unsupported money format");
        }
    }

    public function getName()
    {
        return 'app_extension';
    }
}
