<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckMailboxCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mailbox:check')
            ->setDescription("Checks the connection to the mailbox for security codes")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory = $this->getContainer()->get('App\Mail\MailboxFactory');
        $server = $factory->openSecurityCodesInbox();
        $count = $server->numMessages(); // this will trigger an exception if connection fails
        echo "Check successful. Found $count messages on the inbox.\n";
    }
}
