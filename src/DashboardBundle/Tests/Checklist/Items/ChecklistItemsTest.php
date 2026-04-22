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

namespace SolidInvoice\DashboardBundle\Tests\Checklist\Items;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\DashboardBundle\Checklist\Items\AddTaxRateItem;
use SolidInvoice\DashboardBundle\Checklist\Items\ConfigurePaymentGatewayItem;
use SolidInvoice\DashboardBundle\Checklist\Items\CreateClientItem;
use SolidInvoice\DashboardBundle\Checklist\Items\CustomizeSettingsItem;
use SolidInvoice\DashboardBundle\Checklist\Items\SendInvoiceItem;
use SolidInvoice\DashboardBundle\Checklist\Items\UploadLogoItem;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class ChecklistItemsTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    // ============================================================================
    // UploadLogoItem Tests
    // ============================================================================

    public function testUploadLogoItemReturnsCorrectMetadata(): void
    {
        $item = self::getContainer()->get(UploadLogoItem::class);

        self::assertSame('dashboard.checklist.upload_logo.name', $item->getName());
        self::assertSame('dashboard.checklist.upload_logo.description', $item->getDescription());
        self::assertSame('tabler:photo-up', $item->getIcon());
        self::assertSame('_settings', $item->getRoute());
        self::assertSame(-600, $item->getPriority());
        self::assertTrue($item->active());
    }

    public function testUploadLogoItemIsNotCompleteWhenLogoNotSet(): void
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $systemConfig->set('system/company/logo', null);

        $item = self::getContainer()->get(UploadLogoItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testUploadLogoItemIsNotCompleteWhenLogoIsEmptyString(): void
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $systemConfig->set('system/company/logo', '');

        $item = self::getContainer()->get(UploadLogoItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testUploadLogoItemIsCompleteWhenLogoIsSet(): void
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $systemConfig->set('system/company/logo', '/path/to/logo.png');

        $item = self::getContainer()->get(UploadLogoItem::class);

        self::assertTrue($item->isComplete());
    }

    // ============================================================================
    // CreateClientItem Tests
    // ============================================================================

    public function testCreateClientItemReturnsCorrectMetadata(): void
    {
        $item = self::getContainer()->get(CreateClientItem::class);

        self::assertSame('dashboard.checklist.create_client.name', $item->getName());
        self::assertSame('dashboard.checklist.create_client.description', $item->getDescription());
        self::assertSame('tabler:users', $item->getIcon());
        self::assertSame('_clients_add', $item->getRoute());
        self::assertSame(-300, $item->getPriority());
        self::assertTrue($item->active());
    }

    public function testCreateClientItemIsNotCompleteWhenNoClientsExist(): void
    {
        $item = self::getContainer()->get(CreateClientItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testCreateClientItemIsCompleteWhenClientExists(): void
    {
        ClientFactory::createOne();

        $item = self::getContainer()->get(CreateClientItem::class);

        self::assertTrue($item->isComplete());
    }

    public function testCreateClientItemIsCompleteWhenMultipleClientsExist(): void
    {
        ClientFactory::createMany(3);

        $item = self::getContainer()->get(CreateClientItem::class);

        self::assertTrue($item->isComplete());
    }

    // ============================================================================
    // CustomizeSettingsItem Tests
    // ============================================================================

    public function testCustomizeSettingsItemReturnsCorrectMetadata(): void
    {
        $item = self::getContainer()->get(CustomizeSettingsItem::class);

        self::assertSame('dashboard.checklist.customize_settings.name', $item->getName());
        self::assertSame('dashboard.checklist.customize_settings.description', $item->getDescription());
        self::assertSame('tabler:settings', $item->getIcon());
        self::assertSame('_settings', $item->getRoute());
        self::assertSame(-200, $item->getPriority());
        self::assertFalse($item->active());
    }

    public function testCustomizeSettingsItemIsCompleteWhenTwoSettingsAreConfigured(): void
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $systemConfig->set('system/company/contact_details/address', '123 Main St');
        $systemConfig->set('system/company/contact_details/phone_number', '555-1234');

        $item = self::getContainer()->get(CustomizeSettingsItem::class);

        self::assertTrue($item->isComplete());
    }

    public function testCustomizeSettingsItemIsCompleteWhenAllSettingsAreConfigured(): void
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $systemConfig->set('system/company/contact_details/address', '123 Main St');
        $systemConfig->set('system/company/contact_details/phone_number', '555-1234');
        $systemConfig->set('system/company/contact_details/email', 'hello@example.com');
        $systemConfig->set('system/company/vat_number', 'VAT123456');

        $item = self::getContainer()->get(CustomizeSettingsItem::class);

        self::assertTrue($item->isComplete());
    }

    // ============================================================================
    // AddTaxRateItem Tests
    // ============================================================================

    public function testAddTaxRateItemReturnsCorrectMetadata(): void
    {
        $item = self::getContainer()->get(AddTaxRateItem::class);

        self::assertSame('dashboard.checklist.add_tax_rate.name', $item->getName());
        self::assertSame('dashboard.checklist.add_tax_rate.description', $item->getDescription());
        self::assertSame('tabler:receipt-tax', $item->getIcon());
        self::assertSame('_tax_rates', $item->getRoute());
        self::assertSame(-400, $item->getPriority());
        self::assertFalse($item->active());
    }

    public function testAddTaxRateItemIsNotCompleteWhenNoTaxRatesExist(): void
    {
        $item = self::getContainer()->get(AddTaxRateItem::class);

        self::assertFalse($item->isComplete());
    }

    // ============================================================================
    // SendInvoiceItem Tests
    // ============================================================================

    public function testSendInvoiceItemReturnsCorrectMetadata(): void
    {
        $item = self::getContainer()->get(SendInvoiceItem::class);

        self::assertSame('dashboard.checklist.send_invoice.name', $item->getName());
        self::assertSame('dashboard.checklist.send_invoice.description', $item->getDescription());
        self::assertSame('tabler:file-invoice', $item->getIcon());
        self::assertSame('_invoices_create', $item->getRoute());
        self::assertSame(-400, $item->getPriority());
        self::assertTrue($item->active());
    }

    public function testSendInvoiceItemIsNotCompleteWhenNoInvoicesExist(): void
    {
        $item = self::getContainer()->get(SendInvoiceItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testSendInvoiceItemIsNotCompleteWhenOnlyDraftInvoicesExist(): void
    {
        InvoiceFactory::createOne(['status' => InvoiceStatus::Draft]);

        $item = self::getContainer()->get(SendInvoiceItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testSendInvoiceItemIsCompleteWhenNonDraftInvoiceExists(): void
    {
        InvoiceFactory::createOne(['status' => InvoiceStatus::Pending]);

        $item = self::getContainer()->get(SendInvoiceItem::class);

        self::assertTrue($item->isComplete());
    }

    public function testSendInvoiceItemIsCompleteWhenMixedInvoicesExist(): void
    {
        InvoiceFactory::createOne(['status' => InvoiceStatus::Draft]);
        InvoiceFactory::createOne(['status' => InvoiceStatus::Pending]);

        $item = self::getContainer()->get(SendInvoiceItem::class);

        self::assertTrue($item->isComplete());
    }

    // ============================================================================
    // ConfigurePaymentGatewayItem Tests
    // ============================================================================

    public function testConfigurePaymentGatewayItemReturnsCorrectMetadata(): void
    {
        $item = self::getContainer()->get(ConfigurePaymentGatewayItem::class);

        self::assertSame('dashboard.checklist.configure_payment_gateway.name', $item->getName());
        self::assertSame('dashboard.checklist.configure_payment_gateway.description', $item->getDescription());
        self::assertSame('tabler:credit-card', $item->getIcon());
        self::assertSame('_payment_settings_index', $item->getRoute());
        self::assertSame(-500, $item->getPriority());
        self::assertTrue($item->active());
    }

    public function testConfigurePaymentGatewayItemIsNotCompleteWhenNoPaymentMethodsExist(): void
    {
        $item = self::getContainer()->get(ConfigurePaymentGatewayItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testConfigurePaymentGatewayItemIsNotCompleteWhenOnlyInternalPaymentMethodsExist(): void
    {
        PaymentMethodFactory::createOne(['internal' => true, 'enabled' => true]);

        $item = self::getContainer()->get(ConfigurePaymentGatewayItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testConfigurePaymentGatewayItemIsNotCompleteWhenExternalPaymentMethodIsDisabled(): void
    {
        PaymentMethodFactory::createOne(['internal' => false, 'enabled' => false]);

        $item = self::getContainer()->get(ConfigurePaymentGatewayItem::class);

        self::assertFalse($item->isComplete());
    }

    public function testConfigurePaymentGatewayItemIsCompleteWhenExternalPaymentMethodIsEnabled(): void
    {
        PaymentMethodFactory::createOne(['internal' => false, 'enabled' => true]);

        $item = self::getContainer()->get(ConfigurePaymentGatewayItem::class);

        self::assertTrue($item->isComplete());
    }

    public function testConfigurePaymentGatewayItemIsCompleteWhenMultipleExternalPaymentMethodsExist(): void
    {
        PaymentMethodFactory::createOne(['internal' => false, 'enabled' => true]);
        PaymentMethodFactory::createOne(['internal' => false, 'enabled' => true]);

        $item = self::getContainer()->get(ConfigurePaymentGatewayItem::class);

        self::assertTrue($item->isComplete());
    }
}
