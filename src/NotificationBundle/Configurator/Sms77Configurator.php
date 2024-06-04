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

use SolidInvoice\NotificationBundle\Form\Type\Transport\Sms77Type;
use Symfony\Component\Notifier\Transport\Dsn;
use function sprintf;

final class Sms77Configurator implements ConfiguratorInterface
{
    public static function getName(): string
    {
        return 'Sms77';
    }

    public static function getType(): string
    {
        return 'texter';
    }

    public function getForm(): string
    {
        return Sms77Type::class;
    }

    /**
     * @param array{ api_key: string, from: string } $config
     */
    public function configure(array $config): Dsn
    {
        return new Dsn(sprintf('sms77://%s@default?from=%s', $config['api_key'], $config['from']));
    }
}
