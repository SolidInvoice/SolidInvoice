<?php

namespace CSBill\QuoteBundle\Service;

use CSBill\QuoteBundle\Entity\Quote;
use CSBill\CoreBundle\Manager\SettingsManager;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\Common\Persistence\Mapping\StaticReflectionService;
use Swift_Mailer;

class Email {

    protected $mailer;

    protected $templating;

    protected $dispatcher;

    public function __construct(Swift_Mailer $mailer, SettingsManager $settings)
    {
        $this->mailer = $mailer;
        $this->settings = $settings;
    }

    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;

        return $this;
    }

    public function sendQuote(Quote $quote)
    {
        $htmlTemplate = 'CSBillQuoteBundle:Email:quote.html.twig';

        $message = $this->_createMessage($quote, $htmlTemplate);

        $sent = $this->mailer->send($message);

        return $sent;
    }

    protected function _createMessage($object, $htmlTemplate = null, $textTemplate = null)
    {
        $message = \Swift_Message::newInstance();

        $users = array();

        foreach($object->getUsers() as $user) {
            $users[(string) $user->getDetail('email')] = $user->getFirstname() . ' ' . $user->getLastname();
        }

        $objectType = strtolower($this->getObjectClass($object));

        // TODO : add setting to send html or text emails, and set body accordingly
        $htmlContent = $this->templating->exists($htmlTemplate) ? $this->templating->render($htmlTemplate, array($objectType => $object)) : null;
        $textContent = $this->templating->exists($textTemplate) ? $this->templating->render($textTemplate, array($objectType => $object)) : null;

        $subject = str_replace(array('{id}'), array($object->getId()), $this->settings->get('quote.email_subject'));

        $message->setTo($users)
                ->setSubject($subject)
                ->setFrom($this->settings->get('email.from'))
                ->setBody($htmlContent, 'text/html')
                ->addPart($textContent, 'text/plain');

       return $message;
    }

    public function getObjectClass($object)
    {
        $reflection = new StaticReflectionService();

        return $reflection->getClassShortName(get_class($object));
    }
}