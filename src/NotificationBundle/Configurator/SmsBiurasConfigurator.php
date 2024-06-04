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

use SolidInvoice\NotificationBundle\Form\Type\Transport\SmsBiurasType;
use Symfony\Component\Notifier\Transport\Dsn;
use function sprintf;

final class SmsBiurasConfigurator implements ConfiguratorInterface
{
    public static function getName(): string
    {
        return 'SmsBiuras';
    }

    public static function getType(): string
    {
        return 'texter';
    }

    public function getForm(): string
    {
        return SmsBiurasType::class;
    }

    /**
     * @param array{ uid: string, api_key: string, from: string, test_mode: string } $config
     */
    public function configure(array $config): Dsn
    {
        return new Dsn(sprintf('smsbiuras://%s:%s@default?from=%s&amp;test_mode=%s', $config['uid'], $config['api_key'], $config['from'], $config['test_mode']));
    }
}
