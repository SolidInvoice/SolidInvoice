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
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Twig\Components\PaymentSettings;

final class PaymentSettingsTest extends LiveComponentTest
{
    /**
     * @dataProvider paymentMethodsProvider
     */
    public function testRenderPaymentSettings(string $method): void
    {
        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => $method],
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
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

        $paymentMethod = $paymentMethodRepository->findOneBy(['gatewayName' => 'test-cash']);

        self::assertSame('Test Cash', $paymentMethod->getName());
        self::assertFalse($paymentMethod->isEnabled());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());

        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'test-cash'],
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
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

        $this->assertMatchesHtmlSnapshot($component->render()->toString());

        $component = $this->createLiveComponent(
            name: PaymentSettings::class,
            data: ['method' => 'payex-test'],
            client: $this->client,
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public function paymentMethodsProvider(): iterable
    {
        $paymentFactories = self::getContainer()->get(PaymentFactories::class);

        foreach ($paymentFactories->getFactories() as $method => $factory) {
            yield $method => [$method];
        }
    }
}
