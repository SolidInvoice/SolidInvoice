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

namespace SolidInvoice\PaymentBundle\Config;

use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class ConfigProvider implements ProviderInterface
{
    public const string RECEIPT_AUTO_SEND = 'payment/receipt/auto_send';

    /**
     * @return Config[]
     */
    public function provide(array $data): array
    {
        return [
            new Config(self::RECEIPT_AUTO_SEND, '1', 'settings.description.payment.receipt.auto_send', CheckboxType::class),
        ];
    }
}
