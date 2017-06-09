<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Listener;

use CSBill\SettingsBundle\SystemConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds mailer settings to the env values.
 */
class MailerSettingsEnvListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $installed;

    /**
     * @var SystemConfig
     */
    private $config;

    public function __construct(?string $installed, SystemConfig $config)
    {
        $this->installed = $installed;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
        ];
    }

    public function onKernelRequest()
    {
        if (!$this->installed) {
            return;
        }

        foreach ($this->config->getAll() as $key => $value) {
            if (0 !== strpos($key, 'email') || null === $value) {
                continue;
            }

            putenv(sprintf('mailer_%s=%s', substr($key, strrpos($key, '/') + 1), $value));
        }
    }
}
