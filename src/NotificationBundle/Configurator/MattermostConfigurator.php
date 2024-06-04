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

use SolidInvoice\NotificationBundle\Form\Type\Transport\MattermostType;
use Symfony\Component\Notifier\Transport\Dsn;
use function sprintf;

final class MattermostConfigurator implements ConfiguratorInterface
{
    public static function getName(): string
    {
        return 'Mattermost';
    }

    public static function getType(): string
    {
        return 'chatter';
    }

    public function getForm(): string
    {
        return MattermostType::class;
    }

    /**
     * @param array{ access_token: string, host: string, path: string, channel: string } $config
     */
    public function configure(array $config): Dsn
    {
        return new Dsn(sprintf('mattermost://%s@%s/%s?channel=%s', $config['access_token'], $config['host'], $config['path'], $config['channel']));
    }
}
