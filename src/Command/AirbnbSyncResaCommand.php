<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use DateTime;

class AirbnbSyncResaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('airbnb:syncresaprice')
            ->setDescription("Gathers all reservation prices from mailbox between startDate and endDate")
            ->addArgument("startDate", InputArgument::OPTIONAL, "Sync prices from  - Format YYYY-MM-DD")
            ->addArgument("endDate", InputArgument::OPTIONAL, "Sync prices to - Format YYYY-MM-DD")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startDate = $input->getArgument('startDate');
        $endDate = $input->getArgument('endDate');
        
        if ($startDate == "")
        {
            $startDate = "2018-09-01";
        }
        
        if ($endDate == "")
        {
            $endDate = date("Y-m-d");
        }

        try
        {
            $startPeriod = new DateTime($startDate);
            $endPeriod = new DateTime($endDate);
        }
        catch (\Exception $ex)
        {
            throw new \Exception($ex->getMessage());
        }
        
        if($startPeriod > $endPeriod) {
            throw new \Exception("StartDate cannot be greater than endDate !");
        }
            
        $bot = $this->getContainer()->get('App\Airbnb\AirbnbReservationBot');
        $bot->syncAllReservationPrice($startDate, $endDate);
    }
}
