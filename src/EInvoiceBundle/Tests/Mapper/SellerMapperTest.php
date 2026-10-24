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

namespace SolidInvoice\EInvoiceBundle\Tests\Mapper;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Mapper\SellerMapper;
use SolidInvoice\SettingsBundle\SystemConfig;

final class SellerMapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testMapsSettingsToSellerAddressAndContact(): void
    {
        $systemConfig = $this->systemConfig([
            'system/company/company_name' => 'SolidWorx Ltd',
            'system/company/vat_number' => 'ZA1234567',
            'system/company/contact_details/address' => json_encode([
                'street1' => '1 Main Street',
                'street2' => 'Suite 2',
                'city' => 'Cape Town',
                'state' => 'Western Cape',
                'zip' => '8001',
                'country' => 'ZA',
            ], JSON_THROW_ON_ERROR),
            'system/company/contact_details/phone_number' => '+27210000000',
            'system/company/contact_details/email' => 'billing@solidworx.co',
        ]);

        $seller = new SellerMapper($systemConfig)->map();

        self::assertSame('SolidWorx Ltd', $seller->name);
        self::assertSame('ZA1234567', $seller->vatIdentifier);
        self::assertNull($seller->taxRegistrationIdentifier);
        self::assertNotNull($seller->address);
        self::assertSame('1 Main Street', $seller->address->addressLine1);
        self::assertSame('Suite 2', $seller->address->addressLine2);
        self::assertSame('Cape Town', $seller->address->city);
        self::assertSame('Western Cape', $seller->address->countrySubdivision);
        self::assertSame('8001', $seller->address->postCode);
        self::assertSame('ZA', $seller->address->countryCode);
        self::assertNotNull($seller->contact);
        self::assertSame('+27210000000', $seller->contact->telephone);
        self::assertSame('billing@solidworx.co', $seller->contact->email);
        self::assertNull($seller->contact->name);
    }

    public function testAbsentAddressSettingYieldsNullPostalAddress(): void
    {
        $systemConfig = $this->systemConfig([
            'system/company/company_name' => 'SolidWorx Ltd',
            'system/company/contact_details/address' => null,
        ]);

        $seller = new SellerMapper($systemConfig)->map();

        self::assertNull($seller->address);
    }

    public function testMalformedAddressSettingYieldsNullPostalAddressRatherThanException(): void
    {
        $systemConfig = $this->systemConfig([
            'system/company/company_name' => 'SolidWorx Ltd',
            'system/company/contact_details/address' => '{not valid json',
        ]);

        $seller = new SellerMapper($systemConfig)->map();

        self::assertNull($seller->address);
    }

    public function testAddressOfTheWrongShapeYieldsNullPostalAddress(): void
    {
        $systemConfig = $this->systemConfig([
            'system/company/company_name' => 'SolidWorx Ltd',
            // Valid JSON, but a list rather than the expected object shape.
            'system/company/contact_details/address' => '["1 Main Street","Cape Town"]',
        ]);

        $seller = new SellerMapper($systemConfig)->map();

        self::assertNull($seller->address);
    }

    public function testNoContactSettingsYieldsNullContact(): void
    {
        $systemConfig = $this->systemConfig([
            'system/company/company_name' => 'SolidWorx Ltd',
            'system/company/contact_details/phone_number' => null,
            'system/company/contact_details/email' => null,
        ]);

        $seller = new SellerMapper($systemConfig)->map();

        self::assertNull($seller->contact);
    }

    /**
     * @param array<string, ?string> $values
     */
    private function systemConfig(array $values): SystemConfig
    {
        $systemConfig = M::mock(SystemConfig::class);

        foreach (['system/company/company_name', 'system/company/vat_number', 'system/company/contact_details/address', 'system/company/contact_details/phone_number', 'system/company/contact_details/email'] as $key) {
            $systemConfig->shouldReceive('get')
                ->with($key)
                ->andReturn($values[$key] ?? null);
        }

        return $systemConfig;
    }
}
