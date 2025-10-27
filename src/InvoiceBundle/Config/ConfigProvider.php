<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Config;

use SolidInvoice\CoreBundle\Form\Type\BillingIdConfigurationType;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ConfigProvider implements ProviderInterface
{
    public function provide(array $data): array
    {
        return [
            new Config('invoice/watermark', '1', 'Display a watermark on the invoice with the status', CheckboxType::class),
            new Config('invoice/bcc_address', null, 'Send BCC copy of invoice to this address', EmailType::class),
            new Config('invoice/email_subject', 'New Invoice - #{id}', 'To include the id of the invoice in the subject, add the placeholder {id} where you want the id', TextType::class),
            new Config('invoice/id_generation/strategy', 'auto_increment', '', BillingIdConfigurationType::class),
            new Config('invoice/id_generation/id_prefix', '', 'Example: INV-', TextType::class),
            new Config('invoice/id_generation/id_suffix', '', 'Example: -INV', TextType::class),
        ];
    }
}
