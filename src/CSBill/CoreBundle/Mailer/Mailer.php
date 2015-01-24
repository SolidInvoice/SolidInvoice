<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer;

use CSBill\CoreBundle\Mailer\Events\InvoiceMailEvent;
use CSBill\CoreBundle\Mailer\Events\MailerEvent;
use CSBill\CoreBundle\Mailer\Events\MessageEvent;
use CSBill\CoreBundle\Mailer\Events\QuoteEvent;
use CSBill\CoreBundle\Mailer\Exception\UnexpectedFormatException;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\SettingsBundle\Manager\SettingsManager;
use Swift_Mailer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Templating\EngineInterface;

class Mailer implements MailerInterface
{

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var TokenStorage
     */
    protected $securityToken;

    /**
     * @var SettingsManager
     */
    protected $settings;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param \Swift_Mailer   $mailer
     * @param SettingsManager $settings
     */
    public function __construct(Swift_Mailer $mailer, SettingsManager $settings)
    {
        $this->mailer = $mailer;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    /**
     * {@inheritdoc}
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Set the security instance
     *
     * @param TokenStorage $securityToken
     */
    public function setSecurity(TokenStorage $securityToken)
    {
        $this->securityToken = $securityToken;
    }

    /**
     * Emails an invoice to the customers
     *
     * @param Invoice $invoice
     *
     * @return integer If the email was successfully sent
     */
    public function sendInvoice(Invoice $invoice)
    {
        // TODO : this needs to come from settings or somewhere so it can be extended
        $htmlTemplate = $this->getTemplate(
            'CSBillInvoiceBundle:Email:invoice.html.twig',
            array(
                'invoice' => $invoice,
            )
        );

        $textTemplate = $this->getTemplate(
            'CSBillInvoiceBundle:Email:invoice.txt.twig',
            array(
                'invoice' => $invoice,
            )
        );

        $subject = $this->getSubject('invoice.email_subject', $invoice->getId());

        $users = array();

        foreach ($invoice->getUsers() as $user) {
            /** @var \CSBill\ClientBundle\Entity\Contact $user */
            $users[(string)$user->getPrimaryDetail('email')] = $user->getFirstname().' '.$user->getLastname();
        }

        $event = new InvoiceMailEvent();
        $event->setInvoice($invoice);

        $sent = $this->sendMessage($subject, $users, $htmlTemplate, $textTemplate, $event);

        return $sent;
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return null|string
     */
    protected function getTemplate($template, array $parameters = array())
    {
        return $this->templating->exists($template) ? $this->templating->render($template, $parameters) : null;
    }

    /**
     * Get the subject for an email
     *
     * @param string  $settingsKey
     * @param integer $id
     *
     * @return string
     */
    public function getSubject($settingsKey, $id = null)
    {
        return str_replace('{id}', $id, $this->settings->get($settingsKey));
    }

    /**
     * @param string       $subject
     * @param string|array $users
     * @param string|null  $htmlTemplate
     * @param string|null  $textTemplate
     * @param MessageEvent $event
     *
     * @return int
     * @throws Exception\UnexpectedFormatException
     */
    protected function sendMessage(
        $subject,
        $users,
        $htmlTemplate = null,
        $textTemplate = null,
        MessageEvent $event = null
    ) {
        $message = \Swift_Message::newInstance();

        $fromAddress = (string)$this->settings->get('email.from_address');

        if (!empty($fromAddress)) {
            $fromName = (string)$this->settings->get('email.from_name');

            $message->setFrom($fromAddress, $fromName);
        } else {
            // if a from address is not specified in the config, then we use the currently logged-in users address
            $token = $this->securityToken->getToken();

            $user = $token->getUser();

            $message->setFrom($user->getEmail());
        }

        $message->setSubject($subject)
            ->setTo($users);

        if (null !== $event) {
            $event->setHtmlTemplate($htmlTemplate);
            $event->setTextTemplate($textTemplate);
            $event->setMessage($message);

            $this->dispatcher->dispatch($event->getEvent(), $event);

            $htmlTemplate = $event->getHtmlTemplate();
            $textTemplate = $event->getTextTemplate();
        }

        $format = (string)$this->settings->get('email.format');

        switch ($format) {
            case 'html':
                $message->setBody($htmlTemplate, 'text/html');
                break;

            case 'text':
                $message->setBody($textTemplate, 'text/plain');
                break;

            case 'both':
                $message->setBody($htmlTemplate, 'text/html');
                $message->addPart($textTemplate, 'text/plain');
                break;

            default:
                throw new UnexpectedFormatException($format);
        }

        $mailerEvent = new MailerEvent();
        $mailerEvent->setMessage($message);
        $this->dispatcher->dispatch(MailerEvents::MAILER_SEND, $mailerEvent);

        return $this->mailer->send($message);
    }

    /**
     * Emails a quote to the customers
     *
     * @param Quote $quote
     *
     * @return integer If the email was successfully sent
     */
    public function sendQuote(Quote $quote)
    {
        // TODO : this needs to come from settings or somewhere so it can be extended
        $htmlTemplate = $this->getTemplate('CSBillQuoteBundle:Email:quote.html.twig', array('quote' => $quote));
        $textTemplate = $this->getTemplate('CSBillQuoteBundle:Email:quote.txt.twig', array('quote' => $quote));

        $subject = $this->getSubject('quote.email_subject', $quote->getId());

        $users = array();

        foreach ($quote->getUsers() as $user) {
            /** @var \CSBill\ClientBundle\Entity\Contact $user */
            $users[(string)$user->getPrimaryDetail('email')] = $user->getFirstname().' '.$user->getLastname();
        }

        $event = new QuoteEvent();
        $event->setQuote($quote);

        $sent = $this->sendMessage($subject, $users, $htmlTemplate, $textTemplate, $event);

        return $sent;
    }
}
