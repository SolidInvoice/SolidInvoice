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

namespace SolidInvoice\CoreBundle\Company;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JsonException;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\BillingIdConfigurationType;
use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\NotificationBundle\Form\Type\NotificationType;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Type\AddressType;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidInvoice\TaxBundle\Form\Type\TaxNumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Company\DefaultDataTest
 */
final class DefaultData
{
    private readonly ObjectManager $em;

    public function __construct(ManagerRegistry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * @param array{currency: string} $data
     */
    public function __invoke(Company $company, array $data): void
    {
        $this->createAppConfig($company, $data);
        $this->createContactTypes();
        $this->createPaymentMethods();

        $this->em->flush();
    }

    /**
     * @param array{currency: string} $data
     * @throws JsonException
     */
    private function createAppConfig(Company $company, array $data): void
    {
        $appConfig = [
            // Email
            ['setting_key' => 'email/from_address', 'setting_value' => 'info@solidinvoice.co', 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'email/from_name', 'setting_value' => $company->getName(), 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'email/sending_options/provider', 'setting_value' => null, 'description' => null, 'field_type' => MailTransportType::class],

            // Invoice
            ['setting_key' => 'invoice/bcc_address', 'setting_value' => null, 'description' => 'Send BCC copy of invoice to this address', 'field_type' => EmailType::class],
            ['setting_key' => 'invoice/email_subject', 'setting_value' => 'New Invoice - #{id}', 'description' => 'To include the id of the invoice in the subject, add the placeholder {id} where you want the id', 'field_type' => TextType::class],
            ['setting_key' => 'invoice/id_generation/strategy', 'setting_value' => 'auto_increment', 'description' => '', 'field_type' => BillingIdConfigurationType::class],
            ['setting_key' => 'invoice/id_generation/id_prefix', 'setting_value' => '', 'description' => 'Example: INV-', 'field_type' => TextType::class],
            ['setting_key' => 'invoice/id_generation/id_suffix', 'setting_value' => '', 'description' => 'Example: -INV', 'field_type' => TextType::class],

            // Notification
            ['setting_key' => 'notification/client_create', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'notification/invoice_status_update', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'notification/payment_made', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'notification/quote_status_update', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],

            // Quote
            ['setting_key' => 'quote/bcc_address', 'setting_value' => null, 'description' => 'Send BCC copy of quote to this address', 'field_type' => EmailType::class],
            ['setting_key' => 'quote/email_subject', 'setting_value' => 'New Quotation - #{id}', 'description' => 'To include the id of the quote in the subject, add the placeholder {id} where you want the id', 'field_type' => TextType::class],
            ['setting_key' => 'quote/id_generation/strategy', 'setting_value' => 'auto_increment', 'description' => '', 'field_type' => BillingIdConfigurationType::class],
            ['setting_key' => 'quote/id_generation/id_prefix', 'setting_value' => '', 'description' => 'Example: QUOT-', 'field_type' => TextType::class],
            ['setting_key' => 'quote/id_generation/id_suffix', 'setting_value' => '', 'description' => 'Example: -QUOT', 'field_type' => TextType::class],

            // SMS
            ['setting_key' => 'sms/twilio/number', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'sms/twilio/sid', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'sms/twilio/token', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],

            // System
            ['setting_key' => 'system/company/logo', 'setting_value' => null, 'description' => null, 'field_type' => ImageUploadType::class],
            ['setting_key' => 'system/company/company_name', 'setting_value' => $company->getName(), 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'system/company/contact_details/address', 'setting_value' => null, 'description' => null, 'field_type' => AddressType::class],
            ['setting_key' => 'system/company/contact_details/email', 'setting_value' => null, 'description' => null, 'field_type' => EmailType::class],
            ['setting_key' => 'system/company/contact_details/phone_number', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'system/company/currency', 'setting_value' => $data['currency'], 'description' => null, 'field_type' => CurrencyType::class],
            ['setting_key' => 'system/company/vat_number', 'setting_value' => null, 'description' => null, 'field_type' => TaxNumberType::class],
        ];

        foreach ($appConfig as $setting) {
            $settingEntity = new Setting();
            $settingEntity->setKey($setting['setting_key']);
            $settingEntity->setValue($setting['setting_value']);
            $settingEntity->setDescription($setting['description']);
            $settingEntity->setType($setting['field_type']);

            $this->em->persist($settingEntity);
        }
    }

    private function createContactTypes(): void
    {
        $contactTypes = [
            [
                'name' => 'email',
                'required' => true,
                'type' => 'email',
                'field_options' => [
                    'constraints' => ['email'],
                ],
            ],
            [
                'name' => 'mobile',
                'required' => false,
                'type' => 'text',
                'field_options' => []
            ],
            [
                'name' => 'phone',
                'required' => false,
                'type' => 'text',
                'field_options' => []
            ],
        ];

        foreach ($contactTypes as $contactType) {
            $contactTypeEntity = new ContactType();
            $contactTypeEntity->setName($contactType['name']);
            $contactTypeEntity->setRequired($contactType['required']);
            $contactTypeEntity->setType($contactType['type']);
            $contactTypeEntity->setOptions($contactType['field_options']);

            $this->em->persist($contactTypeEntity);
        }
    }

    private function createPaymentMethods(): void
    {
        $paymentMethods = [
            [
                'name' => 'Cash',
                'gateway_name' => 'cash',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => 'Bank Transfer',
                'gateway_name' => 'bank_transfer',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => 'Credit',
                'gateway_name' => 'credit',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodEntity = new PaymentMethod();
            $paymentMethodEntity->setName($paymentMethod['name']);
            $paymentMethodEntity->setGatewayName($paymentMethod['gateway_name']);
            $paymentMethodEntity->setConfig($paymentMethod['config']);
            $paymentMethodEntity->setInternal($paymentMethod['internal']);
            $paymentMethodEntity->setEnabled($paymentMethod['enabled']);
            $paymentMethodEntity->setFactoryName($paymentMethod['factory']);
            $paymentMethodEntity->setCreated(new DateTime());
            $paymentMethodEntity->setUpdated(new DateTime());

            $this->em->persist($paymentMethodEntity);
        }
    }
}
