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

namespace SolidInvoice\SettingsBundle\Tests;

use const DATE_ATOM;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\SystemConfig;
use function date;

class SystemConfigTest extends TestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    public function testGet(): void
    {
        $config = new SystemConfig(date(DATE_ATOM), $this->em->getRepository(Setting::class));

        static::assertSame('SolidInvoice', $config->get('email/from_name'));
    }

    public function testGetCurrency(): void
    {
        $config = new SystemConfig(date(DATE_ATOM), $this->em->getRepository(Setting::class));

        static::assertInstanceOf(Currency::class, $config->getCurrency());
        static::assertSame('USD', $config->getCurrency()->getCode());
    }

    public function testGetAll(): void
    {
        $config = new SystemConfig(date(DATE_ATOM), $this->em->getRepository(Setting::class));

        self::assertSame([
            'email/from_address' => 'info@solidinvoice.co',
            'email/from_name' => 'SolidInvoice',
            'email/sending_options/provider' => null,
            'invoice/bcc_address' => null,
            'invoice/email_subject' => 'New Invoice - #{id}',
            'invoice/id_generation/id_prefix' => '',
            'invoice/id_generation/id_suffix' => '',
            'invoice/id_generation/strategy' => 'auto_increment',
            'invoice/watermark' => '1',
            'notification/client_create' => '{"email":true,"sms":false}',
            'notification/invoice_status_update' => '{"email":true,"sms":false}',
            'notification/payment_made' => '{"email":true,"sms":false}',
            'notification/quote_status_update' => '{"email":true,"sms":false}',
            'quote/bcc_address' => null,
            'quote/email_subject' => 'New Quotation - #{id}',
            'quote/id_generation/id_prefix' => '',
            'quote/id_generation/id_suffix' => '',
            'quote/id_generation/strategy' => 'auto_increment',
            'quote/watermark' => '1',
            'sms/twilio/number' => null,
            'sms/twilio/sid' => null,
            'sms/twilio/token' => null,
            'system/company/company_name' => 'SolidInvoice',
            'system/company/contact_details/address' => null,
            'system/company/contact_details/email' => null,
            'system/company/contact_details/phone_number' => null,
            'system/company/currency' => 'USD',
            'system/company/logo' => null,
            'system/company/vat_number' => null,
        ], $config->getAll());
    }

    public function testInvalidGet(): void
    {
        $config = new SystemConfig(date(DATE_ATOM), $this->em->getRepository(Setting::class));

        self::assertNull($config->get('some/invalid/key'));
    }
}
