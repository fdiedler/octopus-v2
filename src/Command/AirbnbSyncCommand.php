<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class AirbnbSyncCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('airbnb:sync')
            ->setDescription("Gathers all the Property/Reservation objects from Airbnb for each Host")
            ->addArgument("direction", InputArgument::OPTIONAL, "only|after|failures, to be used with the email argument")
            ->addArgument("email", InputArgument::OPTIONAL, "Wehost email alias of the host whose properties are to be synced")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');
        $direction = $input->getArgument('direction');
        if($direction != null && !in_array($direction, [ "only", "after", "failures", "failures-after" ])) {
            throw new \Exception("Unsupported direction: $direction");
        }
        $bot = $this->getContainer()->get('App\Airbnb\AirbnbBot');
        $bot->syncAllProperties($email, $direction);
    }
}
