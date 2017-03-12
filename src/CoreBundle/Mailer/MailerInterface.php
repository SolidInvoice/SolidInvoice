<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer;

use CSBill\SettingsBundle\Manager\SettingsManager;
use Swift_Mailer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

interface MailerInterface
{
    /**
     * Constructor.
     *
     * @param Swift_Mailer    $mailer
     * @param SettingsManager $settings
     */
    public function __construct(Swift_Mailer $mailer, SettingsManager $settings);

    /**
     * Sets the templating instance.
     *
     * @param EngineInterface $templating
     */
    public function setTemplating(EngineInterface $templating);

    /**
     * Sets the event dispatcher instance.
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return Mailer
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);
}
