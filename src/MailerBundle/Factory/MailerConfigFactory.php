<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2020
 */

namespace SolidInvoice\MailerBundle\Factory;

use SolidInvoice\MailerBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Transport;

final class MailerConfigFactory
{
    private const CONFIG_KEY = 'email/sending_options/provider';

    private $config;

    private $inner;

    /**
     * @var ConfiguratorInterface[]
     */
    private $transports;

    public function __construct(Transport $inner, SystemConfig $config, iterable $transports)
    {
        $this->config = $config;
        $this->inner = $inner;
        $this->transports = $transports;
    }

    /**
     * @throws \JsonException
     */
    public function fromStrings()
    {
        $config = \json_decode($this->config->get(self::CONFIG_KEY), true, 512, JSON_THROW_ON_ERROR);
        $provider = $config['provider'] ?? '';

        foreach ($this->transports as $transport) {
            if  ($transport->getName() === $provider) {
                return $this->inner->fromDsnObject($transport->configure($config['config'] ?? []));
            }
        }

        throw new \RuntimeException('Invalid mailer config');
    }
}