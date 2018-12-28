<?php
 
namespace App\Manager;
 
class MailManager
{
    protected $mailer;
    protected $twig;
    protected $from;
    
    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->from = "no-reply@wehost.fr";
    }
 
    /**
     * Send email
     *
     * @param   string   $template      email template
     * @param   mixed    $parameters    custom params for template
     * @param   string   $to            to email address or array of email addresses
     *
     * @return  boolean                 send status
     */
    public function sendEmail($template, $parameters, $to)
    {
        $template = $this->twig->loadTemplate('mail/' . $template . '.html.twig');
 
        $subject  = $template->renderBlock('subject', $parameters);
        $bodyHtml = $template->renderBlock('body_html', $parameters);
        $bodyText = $template->renderBlock('body_text', $parameters);
 
        try {
            $message = (new \Swift_Message($subject))
                ->setFrom($this->from, "No-reply - WeHost")
                ->setTo($to)
                ->setBody($bodyHtml, 'text/html')
                ->addPart($bodyText, 'text/plain')
            ;
            $response = $this->mailer->send($message);
 
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
 
        return $response;
    }
}