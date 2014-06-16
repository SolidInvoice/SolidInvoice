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

use CSBill\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Swift_Mailer;

interface MailerInterface
{
    /**
     * Constructor
     *
     * @param  Swift_Mailer    $mailer
     * @param  SettingsManager $settings
     * @return void
     */
    public function __construct(Swift_Mailer $mailer, SettingsManager $settings);

    /**
     * Sets the templating instance
     *
     * @param  EngineInterface $templating
     * @return void
     */
    public function setTemplating(EngineInterface $templating);

    /**
     * Sets the event dispatcher instance
     * @param  EventDispatcherInterface $eventDispatcher
     * @return Mailer
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);
}
