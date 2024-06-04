<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SensitiveParameter;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Notification\Transports;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Notifier\Transport;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NotificationTransportFactory
{
    public function __construct(
        private readonly Transport $transport,
        private readonly TransportSettingRepository $transportSettingRepository,
        #[TaggedLocator(tag: ConfiguratorInterface::DI_TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $transportConfigurations,
    ) {
    }

    public static function fromDsn(#[SensitiveParameter] string $dsn, ?EventDispatcherInterface $dispatcher = null, ?HttpClientInterface $client = null): TransportInterface
    {
        return Transport::fromDsn($dsn, $dispatcher, $client);
    }

    /**
     * @param array<string> $dsns
     */
    public static function fromDsns(#[SensitiveParameter] array $dsns, ?EventDispatcherInterface $dispatcher = null, ?HttpClientInterface $client = null): TransportInterface
    {
        return Transport::fromDsns($dsns, $dispatcher, $client);
    }

    /**
     * @param array<string> $dsns
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function fromStrings(#[SensitiveParameter] array $dsns): Transports
    {
        $transports = [];

        foreach ($this->transportSettingRepository->findAll() as $setting) {
            $configurator = $this->transportConfigurations->get($setting->getTransport());
            assert($configurator instanceof ConfiguratorInterface);

            $transports[$setting->getId()->toString()] = $this->transport->fromDsnObject($configurator->configure($setting->getSettings()));
        }

        return new Transports($transports);
    }

    public function fromString(#[SensitiveParameter] string $dsn): TransportInterface
    {
        return self::fromDsns([$dsn]);
    }

    public function fromDsnObject(Dsn $dsn): TransportInterface
    {
        return $this->transport->fromDsnObject($dsn);
    }
}
