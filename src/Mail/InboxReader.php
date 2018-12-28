<?php

namespace App\Mail;
use Fetch\Server;

/**
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class InboxReader
{
    const SSL_IMAP_PORT = 993;
    
    private $serverConn;
    private $securityCodesHost;
    private $securityCodesEmail;
    private $securityCodesPassword;

    public function __construct(
        string $securityCodesHost,
        string $securityCodesEmail,
        string $securityCodesPassword
    ) {
        $this->securityCodesHost = $securityCodesHost;
        $this->securityCodesEmail = $securityCodesEmail;
        $this->securityCodesPassword = $securityCodesPassword;
    }

    // Open the IMAP connection
    public function openConnectionInbox()
    {
        $this->serverConn = new Server($this->securityCodesHost, self::SSL_IMAP_PORT);
        $this->serverConn->setAuthentication($this->securityCodesEmail, $this->securityCodesPassword);
        $this->serverConn->setMailBox('INBOX');
    }
    
    // Search all messages that meets some criterias in the current mailbox
    public function searchCurrentMailBox($criteria = "ALL")
    {
        $messages = $this->serverConn->search($criteria);
        return $messages;
    }
    
    // Search all messages that meets some criterias in all mailboxes available
    public function searchAllMailBoxes($mailboxes = null, $criteria = "ALL", $stopAtFirstMatch = false)
    {
        $allMessages = array();
        if ($mailboxes == null)
            $mailboxes = $this->listMailBox();
     
        foreach($mailboxes as $mailbox)
        {
            // Get the name of this mailBox
            $shortname = (str_replace("{".$this->securityCodesHost.":".self::SSL_IMAP_PORT."/ssl}", '', $mailbox));
            
            // Try to connect to it
            if ($this->serverConn->setMailBox($shortname) === true)
            {
                // Search messages inside this mailbox
                $messages = $this->serverConn->search($criteria);
                
                if (count($messages) > 0)
                {
                    foreach($messages as $message)
                    {
                        // Add messages found
                        $allMessages[] = array("subject" => $message->getOverview()->subject, "plaintext" => $message->getPlainTextBody());
                    }
                    
                    // Break as we found a match if asked
                    if ($stopAtFirstMatch)
                        return $allMessages;
                }
            }
        }

        return $allMessages;
    }
    
    // List all available mailboxes according to a pattern
    public function listMailBox($pattern = "*")
    {
       $mailBoxes = $this->serverConn->listMailBoxes($pattern);
       return $mailBoxes;
    }
    
    // Get a mailbox details
    public function getMailBoxDetails($mailbox)
    {
       $details = $this->serverConn->getMailBoxDetails($mailbox);
       return $details;
    }
    
    // Get all messages from the current mailbox
    public function getMessages($limit = null)
    {
       $messages = $this->serverConn->getMessages($limit);
       return $messages;
    }
    
}
