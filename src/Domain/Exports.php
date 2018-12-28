<?php

namespace App\Domain;

use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Money;
use App\Domain\Money as MoneyDomain;
use Exporter\Writer\XlsWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use DateTime;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class Exports
{

    private $moneyDomain;
    private $reservationRepository;

    private $metadata = [
        'exportReservations' => [ 'extension' => 'xls', 'mime' => 'application/vnd.ms-excel' ]
    ];

    public function __construct(ManagerRegistry $doctrine, MoneyDomain $moneyDomain)
    {
        $this->moneyDomain = $moneyDomain;
        $this->reservationRepository = $doctrine->getRepository('App\Entity\Reservation');
    }

    public function exportReservations(string $filename): void
    {
        $columns = [
            'Property' => 'property.label',
            'Market' => 'property.market',
            'Airbnb Code' => 'airbnbId',
            'Status' => 'status',
            'Check-In' => 'checkinDate',
            'Check-Out' => 'checkoutDate',
            'Guest Name' => 'guestName',
            'Guest Phone' => 'guestPhoneNumber',
            'Host Profit' => 'hostProfit',
            'Guest Cleaning Fee' => 'guestCleaningFee',
            'Host' => 'property.host.name',
            'Host Email Alias' => 'property.host.wehostEmailAlias',
            'WeHost Percentage' => 'property.wehostPercentage',
            'Host Cleaning Fee' => 'property.cleaningFee',
        ];

        $query = $this->reservationRepository->findAllManagedQueryWithCompleteGraph();

        $writer = new XlsWriter($filename);
        $propertyAccess = PropertyAccess::createPropertyAccessor();

        $writer->open();
        foreach($query->getResult() as $row) {
            $data = [];
            foreach($columns as $name => $key) {
                $data[$name] = $this->format($propertyAccess->getValue($row, $key));
            }
            $writer->write($data);
        }
        $writer->close();
    }

    public function streamed(string $methodName): Response
    {
        $metadata = $this->metadata[$methodName];
        $filename = sprintf(
            'reservations_%s_' . time() . '.%s',
            date('Y_m_d', strtotime('now')),
            $metadata['extension']
        );
        $callback = function() use($methodName) {
            $this->$methodName('php://output');
        };
        return new StreamedResponse($callback, 200, array(
            'Content-Type'        => $metadata['mime'],
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename)
        ));
    }

    public function format($value): ?string
    {
        if($value instanceof Money) {
            return $this->moneyDomain->getFormatter()->format($value);
        }

        if($value instanceof DateTime) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

}
