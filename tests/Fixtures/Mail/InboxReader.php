<?php

namespace App\Tests\Fixtures\Mail;

use App\Mail\InboxReader as BaseInboxReader;
use Fetch\Server;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class InboxReader extends BaseInboxReader
{
    private $fakeEmails;
    
    private function dirToArray($dir)
    {
        $result = array();

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value)
        {
            if (!in_array($value,array(".","..")))
            {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                {
                    $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                }
                else
                {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }    

    public function openConnectionInbox()
    {
        $basePath = __DIR__.'/../../samples/airbnb/mails/';
        
        // Read fake emails from hard disk_free_space
        $files = $this->dirToArray($basePath);
        //print_r($files);
        
        foreach ($files as $filename)
        {
            $this->fakeEmails[] = array(
                "subject" => $filename,
                "plaintext" => file_get_contents($basePath.$filename)
            );
        }
    }
    
    public function searchAllMailBoxes($mailboxes = null, $criteria = "ALL", $stopAtFirstMatch = false)
    {
        $resaReference = str_replace("\"", "", $criteria);
        $resaReference = str_replace("BODY ", "", $resaReference);
        
        $allMessages = array();
        foreach ($this->fakeEmails as $fakeEmail)
        {
            // Search the reference inside the fake mail
            $pos = strpos($fakeEmail["plaintext"], $resaReference);
            if ($pos !== false)
            {
                $allMessages[] = $fakeEmail;
                
                // Break as we found a match if asked
                if ($stopAtFirstMatch)
                    return $allMessages;
            }
        }
        
        //print_r($allMessages);
        
        return $allMessages;
    }
}