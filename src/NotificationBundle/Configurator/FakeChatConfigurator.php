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

use SolidInvoice\NotificationBundle\Form\Type\Transport\FakeChatType;
use Symfony\Component\Notifier\Transport\Dsn;
use function sprintf;

/**
 * @codeCoverageIgnore
 */
final class FakeChatConfigurator implements ConfiguratorInterface
{
    public static function getName(): string
    {
        return 'FakeChat';
    }

    public static function getType(): string
    {
        return 'chatter';
    }

    public function getForm(): string
    {
        return FakeChatType::class;
    }

    /**
     * @param array{ to: string, from: string } $config
     */
    public function configure(array $config): Dsn
    {
        return new Dsn(sprintf('fakechat+email://default?to=%s&amp;from=%s', $config['to'], $config['from']));
    }
}
