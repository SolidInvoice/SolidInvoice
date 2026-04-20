<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Tests\Twig\Components;

use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Twig\Components\PaymentSettings;

final class PaymentSettingsTest extends LiveComponentTest
{
    #[DataProvider('paymentMethodsProvider')]
    public function testRenderPaymentSettings(string $method): void
    {
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => $method],
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    public function testSavePaymentSettings(): void
    {
        $this->csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true);

        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        $paymentMethodRepository = $doctrine->getRepository(PaymentMethod::class);

        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'cash'],
            client: $this->client,
        )->actingAs($this->getUser());

        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'cash']);

        self::assertSame('Cash', $paymentMethod->getName());
        self::assertTrue($paymentMethod->isEnabled());

        $component->set('payment_methods', [
            'name' => 'Test Cash',
            'config' => [],
            'enabled' => false,
        ])->call('save');

        // Gateway name should NOT change for existing payment methods
        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'cash']);

        self::assertSame('Test Cash', $paymentMethod->getName());
        self::assertSame('cash', $paymentMethod->getGatewayName()); // Gateway name preserved
        self::assertFalse($paymentMethod->isEnabled());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));

        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'cash'], // Use original gateway name
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    public function testCreateNewPaymentSettings(): void
    {
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        $paymentMethodRepository = $doctrine->getRepository(PaymentMethod::class);

        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'payex'],
            client: $this->client,
        )->actingAs($this->getUser());

        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'payex']);

        self::assertNull($paymentMethod);

        $component->set('payment_methods', [
            'name' => 'Payex Test',
            'config' => [
                'account_number' => 12345,
                'encryption_key' => 'foo-bar-baz',
                'sandbox' => true,
            ],
            'enabled' => true,
        ])->call('save');

        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'payex-test']);

        self::assertSame('Payex Test', $paymentMethod->getName());
        self::assertSame('payex', $paymentMethod->getFactoryName());
        self::assertSame('payex-test', $paymentMethod->getGatewayName());
        self::assertTrue($paymentMethod->isEnabled());
        self::assertSame([
            'factory' => 'payex',
            'account_number' => '12345',
            'encryption_key' => 'foo-bar-baz',
            'sandbox' => true,
        ], $paymentMethod->getConfig());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));

        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'payex-test'],
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    public function testShowDeleteConfirmation(): void
    {
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'cash'],
            client: $this->client,
        )->actingAs($this->getUser());

        // Initially showDeleteConfirmation should be false
        $rendered = $component->render()->toString();
        self::assertStringNotContainsString('delete-confirmation-modal', $rendered);

        // Call showDeleteConfirmation action
        $component = $component->call('showDeleteConfirmation');

        // After calling showDeleteConfirmation, the modal should appear
        $rendered = $component->render()->toString();
        self::assertStringContainsString('delete-confirmation-modal', $rendered);
    }

    public function testCancelDelete(): void
    {
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'cash'],
            client: $this->client,
        )->actingAs($this->getUser());

        // First show the delete confirmation modal
        $component = $component->call('showDeleteConfirmation');
        $rendered = $component->render()->toString();
        self::assertStringContainsString('delete-confirmation-modal', $rendered);

        // Now cancel the delete
        $component = $component->call('cancelDelete');

        // After calling cancelDelete, modal should be hidden
        $rendered = $component->render()->toString();
        self::assertStringNotContainsString('delete-confirmation-modal', $rendered);
    }

    public function testPasswordPreservation(): void
    {
        $this->csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true);

        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $paymentMethodRepository = $doctrine->getRepository(PaymentMethod::class);

        // Create a payment method with a password
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'payex'],
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('payment_methods', [
            'name' => 'Payex Test',
            'config' => [
                'account_number' => '12345',
                'encryption_key' => 'secret-password-123',
                'sandbox' => true,
            ],
            'enabled' => true,
        ])->call('save');

        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'payex-test']);
        self::assertSame('secret-password-123', $paymentMethod->getConfig()['encryption_key']);

        // Update with a new password
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'payex-test'],
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('payment_methods', [
            'name' => 'Payex Updated',
            'config' => [
                'account_number' => '54321',
                'encryption_key' => 'new-password-456',
                'sandbox' => true, // Keep as true to avoid checkbox handling issues
            ],
            'enabled' => true,
        ])->call('save');

        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'payex-test']);
        self::assertSame('Payex Updated', $paymentMethod->getName());
        self::assertSame('54321', $paymentMethod->getConfig()['account_number']);
        // Password should be updated to the new value
        self::assertSame('new-password-456', $paymentMethod->getConfig()['encryption_key']);
    }

    public function testModalClosesAfterSave(): void
    {
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'cash'],
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('payment_methods', [
            'name' => 'Cash Updated',
            'config' => [],
            'enabled' => true,
        ])->call('save');

        // Verify redirect doesn't include selectedGateway parameter
        $rendered = $component->render()->toString();
        self::assertStringContainsString('/payments/methods', $rendered);
        self::assertStringNotContainsString('selectedGateway', $rendered);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function paymentMethodsProvider(): iterable
    {
        static::bootKernel();
        $paymentFactories = static::getContainer()->get(PaymentFactories::class);

        foreach ($paymentFactories->getFactories() as $method => $factory) {
            yield $method => [$method];
        }
    }
}
