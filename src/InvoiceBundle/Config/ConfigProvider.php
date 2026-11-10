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

namespace SolidInvoice\InvoiceBundle\Config;

use SolidInvoice\CoreBundle\Form\Type\BillingIdConfigurationType;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\InvoiceBundle\Form\Type\PaymentTermsSettingType;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class ConfigProvider implements ProviderInterface
{
    /**
     * @return Config[]
     */
    public function provide(array $data): array
    {
        return [
            new Config('invoice/watermark', '1', 'settings.description.invoice.watermark', CheckboxType::class),
            new Config('invoice/bcc_address', null, 'settings.description.invoice.bcc_address', EmailType::class),
            new Config('invoice/email_subject', 'New Invoice - #{id}', 'settings.description.invoice.email_subject', TextType::class),
            new Config(PaymentTerms::CONFIG_PATH, PaymentTerms::Net30->value, 'settings.description.invoice.payment_terms', PaymentTermsSettingType::class),
            new Config('invoice/payment_instructions/remittance_reference', '{id}', 'settings.description.invoice.payment_instructions.remittance_reference', TextType::class),
            new Config('invoice/id_generation/strategy', 'auto_increment', '', BillingIdConfigurationType::class),
            new Config('invoice/id_generation/id_prefix', '', 'settings.description.invoice.id_generation.id_prefix', TextType::class),
            new Config('invoice/id_generation/id_suffix', '', 'settings.description.invoice.id_generation.id_suffix', TextType::class),
            new Config(
                'invoice/reminder/enabled',
                '1',
                'settings.description.invoice.reminder.enabled',
                CheckboxType::class,
                ['feature_gated' => Feature::AutomatedReminders->value],
            ),
            new Config(
                'invoice/reminder/pre_due_enabled',
                '1',
                'settings.description.invoice.reminder.pre_due_enabled',
                CheckboxType::class,
                ['feature_gated' => Feature::AutomatedReminders->value],
            ),
            new Config(
                'invoice/reminder/pre_due_days',
                '3',
                'settings.description.invoice.reminder.pre_due_days',
                IntegerType::class,
                ['attr' => ['min' => 0, 'max' => 30], 'feature_gated' => Feature::AutomatedReminders->value],
            ),
        ];
    }
}
