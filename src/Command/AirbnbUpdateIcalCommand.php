<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Cron;

class AirbnbUpdateIcalCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('airbnb:updateical')
            ->setDescription("Update ical file stored in local in the server.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        // Add this job in the database
        $cron = new Cron("Update local iCal");
        $em->persist($cron);
        $em->flush();
        
        $icalManager = $this->getContainer()->get('App\Http\IcalManager');
        $log = $icalManager->updateLocalIcal();
        
        $cron->setFinished(true);
        $cron->setDetails($log);
        $em->persist($cron);
        $em->flush();
    }
}
