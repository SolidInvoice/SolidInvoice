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

namespace SolidInvoice\NotificationBundle\Configurator;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Notifier\Transport\Dsn;

#[AutoconfigureTag(ConfiguratorInterface::DI_TAG)]
interface ConfiguratorInterface
{
    public const DI_TAG = 'notification.configurator';

    public static function getName(): string;

    public static function getType(): string;

    public function getForm(): string;

    /**
     * @param array<string, string> $config
     */
    public function configure(array $config): Dsn;
}
