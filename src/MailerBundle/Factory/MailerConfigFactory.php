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
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\MailerBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\MailerBundle\Transport\CompanyTransport;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Uid\Ulid;
use function implode;
use function json_decode;

/**
 * @see \SolidInvoice\MailerBundle\Tests\Factory\MailerConfigFactoryTest
 */
final class MailerConfigFactory
{
    public const string CONFIG_KEY = 'email/sending_options/provider';

    /**
     * @var array<string, TransportInterface>
     */
    private array $resolved = [];

    /**
     * @param iterable<ConfiguratorInterface> $transports
     */
    public function __construct(
        private readonly Transport $inner,
        private readonly SystemConfig $config,
        private readonly iterable $transports,
        private readonly CompanySelectorInterface $companySelector,
    ) {
    }

    /**
     * @param list<string> $dsns
     */
    public function fromStrings(array $dsns = []): TransportInterface
    {
        return new CompanyTransport(fn (): TransportInterface => $this->resolve($dsns));
    }

    /**
     * @param list<string> $dsns
     */
    public function resolve(array $dsns = []): TransportInterface
    {
        // Mail settings belong to a company. Without one selected the settings
        // query is unscoped and would return whichever company's row comes first.
        $mailerConfig = $this->companySelector->getCompany() instanceof Ulid ? $this->config->get(self::CONFIG_KEY) : null;

        return $this->resolved[$mailerConfig . "\0" . implode(',', $dsns)] ??= $this->create($mailerConfig, $dsns);
    }

    /**
     * @param list<string> $dsns
     */
    private function create(?string $mailerConfig, array $dsns): TransportInterface
    {
        if (null === $mailerConfig) {
            return $this->inner->fromStrings($dsns);
        }

        try {
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
