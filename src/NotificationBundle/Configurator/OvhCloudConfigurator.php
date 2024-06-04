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

// !! This file is autogenerated. Do not edit. !!

namespace SolidInvoice\NotificationBundle\Configurator;

use SolidInvoice\NotificationBundle\Form\Type\Transport\OvhCloudType;
use Symfony\Component\Notifier\Transport\Dsn;
use function sprintf;
use function urlencode;

/**
 * @codeCoverageIgnore
 */
final class OvhCloudConfigurator implements ConfiguratorInterface
{
    public static function getName(): string
    {
        return 'OvhCloud';
    }

    public static function getType(): string
    {
        return 'texter';
    }

    public function getForm(): string
    {
        return OvhCloudType::class;
    }

    /**
     * @param array{ application_key: string, application_secret: string, consumer_key: string, service_name: string } $config
     */
    public function configure(array $config): Dsn
    {
        return new Dsn(sprintf('ovhcloud://%s:%s@default?consumer_key=%s&amp;service_name=%s', urlencode($config['application_key']), urlencode($config['application_secret']), urlencode($config['consumer_key']), urlencode($config['service_name'])));
    }
}
