<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Entity\Cron;

class GenerateInvoicesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('invoices:generate')
            ->setDescription("Generates all the invoices for the given month")
            ->addArgument('year', InputArgument::OPTIONAL)
            ->addArgument('month', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $year = $input->getArgument('year');
        $month = $input->getArgument('month');
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        // Generate for current year by default
        if ($year == null)
            $year = date("Y");
        
        // Add this job in the database
        $cron = new Cron("Invoices update for $year".($month != null ? "-$month" : ""));
        $em->persist($cron);
        $em->flush();
        
        if ($month == null)
        {
            // Generate invoices for all mounth of the given year
            for ($month=1; $month<=12; $month++)
            {
                $this->getContainer()->get('App\Manager\InvoiceManager')->generateInvoices($year, $month);
            }
        }
        else
        {
            $this->getContainer()->get('App\Manager\InvoiceManager')->generateInvoices($year,$month);
        }
        
        $cron->setFinished(true);
        $em->persist($cron);
        $em->flush(); 
    }
}
