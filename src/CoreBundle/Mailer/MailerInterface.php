<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Mailer;

use SolidInvoice\SettingsBundle\SystemConfig;
use Swift_Mailer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

interface MailerInterface
{
    /**
     * @param Swift_Mailer $mailer
     * @param SystemConfig $settings
     */
    public function __construct(Swift_Mailer $mailer, SystemConfig $settings);

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
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): Mailer;
}
