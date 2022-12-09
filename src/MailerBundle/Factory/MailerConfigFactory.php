<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Factory;

use JsonException;
use RuntimeException;
use SolidInvoice\MailerBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * @see \SolidInvoice\MailerBundle\Tests\Factory\MailerConfigFactoryTest
 */
final class MailerConfigFactory
{
    private const CONFIG_KEY = 'email/sending_options/provider';

    private SystemConfig $config;

    private Transport $inner;

    /**
     * @var iterable<ConfiguratorInterface>
     */
    private iterable $transports;

    private bool $demoMode;

    /**
     * @param iterable<ConfiguratorInterface> $transports
     */
    public function __construct(Transport $inner, SystemConfig $config, iterable $transports, bool $demoMode)
    {
        $this->config = $config;
        $this->inner = $inner;
        $this->transports = $transports;
        $this->demoMode = $demoMode;
    }

    /**
     * @throws JsonException
     */
    public function fromStrings(): TransportInterface
    {
        if ($this->demoMode) {
            // Disable email sending in demo mode
            return $this->inner->fromString('null://null');
        }

        $config = \json_decode($this->config->get(self::CONFIG_KEY), true, 512, JSON_THROW_ON_ERROR);
        $provider = $config['provider'] ?? '';

        foreach ($this->transports as $transport) {
            if ($transport->getName() === $provider) {
                return $this->inner->fromDsnObject($transport->configure($config['config'] ?? []));
            }
        }

        throw new RuntimeException('Invalid mailer config');
    }
}
