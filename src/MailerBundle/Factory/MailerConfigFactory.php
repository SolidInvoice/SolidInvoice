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
use function json_decode;

/**
 * @see \SolidInvoice\MailerBundle\Tests\Factory\MailerConfigFactoryTest
 */
final class MailerConfigFactory
{
    public const CONFIG_KEY = 'email/sending_options/provider';

    /**
     * @param iterable<ConfiguratorInterface> $transports
     */
    public function __construct(
        private readonly Transport $inner,
        private readonly SystemConfig $config,
        private readonly iterable $transports
    ) {
    }

    /**
     * @param list<string> $dsns
     */
    public function fromStrings(array $dsns = []): ?TransportInterface
    {
        try {
            $mailerConfig = $this->config->get(self::CONFIG_KEY);

            if (null === $mailerConfig) {
                return $this->inner->fromStrings($dsns);
            }

            $config = json_decode($mailerConfig, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Invalid mailer config', $e->getCode(), $e);
        }

        $provider = $config['provider'] ?? '';

        foreach ($this->transports as $transport) {
            if ($transport->getName() === $provider) {
                return $this->inner->fromDsnObject($transport->configure($config['config'] ?? []));
            }
        }

        throw new RuntimeException('Invalid mailer config');
    }
}
