<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Config;

use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ConfigProvider implements ProviderInterface
{
    public function provide(array $data): array
    {
        return [
            new Config('email/from_address', 'no-reply@solidinvoice.co', null, TextType::class),
            new Config('email/from_name', $data['company_name'] ?? '', null, TextType::class),
            new Config('email/sending_options/provider', null, null, MailTransportType::class),
        ];
    }
}
