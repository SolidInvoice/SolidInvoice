<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Config;

use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use SolidInvoice\SettingsBundle\Form\Type\AddressType;
use SolidInvoice\TaxBundle\Form\Type\TaxNumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class SystemConfigProvider implements ProviderInterface
{
    public function provide(array $data): array
    {
        return [
            new Config('system/company/logo', null, null, ImageUploadType::class),
            new Config('system/company/company_name', $data['company_name'] ?? null, null, TextType::class),
            new Config('system/company/contact_details/address', null, null, AddressType::class),
            new Config('system/company/contact_details/email', null, null, EmailType::class),
            new Config('system/company/contact_details/phone_number', null, null, TextType::class),
            new Config('system/company/currency', $data['currency'] ?? null, null, CurrencyType::class),
            new Config('system/company/vat_number', null, null, TaxNumberType::class),
        ];
    }
}
