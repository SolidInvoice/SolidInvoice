<?php

namespace CSBill\CoreBundle\Mailer;

use CS\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Swift_Mailer;

interface MailerInterface
{
    /**
     * Constructor
     *
     * @param Swift_Mailer $mailer
     * @param SettingsManager $settings
     */
    public function __construct(Swift_Mailer $mailer, SettingsManager $settings);

    /**
     * Sets the templating instance
     *
     * @param EngineInterface $templating
     */
    public function setTemplating(EngineInterface $templating);

    /**
     * Sets the event dispatcher instance
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);
}
