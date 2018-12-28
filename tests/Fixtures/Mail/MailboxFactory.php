<?php

namespace App\Tests\Fixtures\Mail;

use App\Mail\MailboxFactory as BaseMailboxFactory;
use Fetch\Server;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class MailboxFactory extends BaseMailboxFactory
{
    public function openSecurityCodesInbox(): Server
    {
        throw new \Exception("Not implemented yet");
    }

}
