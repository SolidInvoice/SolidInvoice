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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Type\ClientType;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS feature gate at the ClientType form level: when
 * `multi_currency` is disabled the currencyCode field is rendered disabled
 * with the company default currency, and submission overwrites any submitted
 * value with the company default. When the feature is enabled (or in the
 * self-hosted NoopFeatureGate scenario), the field stays editable.
 */
#[Group('functional')]
final class ClientFormMultiCurrencyGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testFormGatesCurrencyCodeFieldWhenFeatureDisabled(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $key !== Feature::MultiCurrency->value);

        self::getContainer()->set(FeatureGate::class, $featureGate);

        $client = new Client();
        $client->setName('Acme Corp');
        $client->setCurrencyCode('EUR');

        $form = $this->factory()->create(ClientType::class, $client);

        $field = $form->get('currencyCode');
        self::assertTrue($field->isDisabled(), 'currencyCode should be disabled when multi_currency is gated');
        self::assertSame(Feature::MultiCurrency->value, $field->getConfig()->getOption('feature_gated'));

        // Submit with a non-default currency — the SUBMIT listener overrides the
        // entity's currencyCode to the company default ("USD" via test fixture).
        $form->submit([
            'name' => $client->getName(),
            'currencyCode' => 'EUR',
            'contacts' => [],
            'addresses' => [],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('USD', $client->getCurrencyCode());
    }

    public function testFormKeepsCurrencyCodeFieldEditableWhenFeatureEnabled(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        self::getContainer()->set(FeatureGate::class, $featureGate);

        $client = new Client();
        $client->setName('Acme Corp');

        $form = $this->factory()->create(ClientType::class, $client);

        $field = $form->get('currencyCode');
        self::assertFalse($field->isDisabled(), 'currencyCode should be editable when multi_currency is enabled');
        self::assertNull($field->getConfig()->getOption('feature_gated'));

        $form->submit([
            'name' => $client->getName(),
            'currencyCode' => 'EUR',
            'contacts' => [],
            'addresses' => [],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('EUR', $client->getCurrencyCode());
    }

    public function testSelfHostedKeepsCurrencyCodeEditable(): void
    {
        // The container's wired FeatureGate alias resolves to NoopFeatureGate
        // in non-SaaS deployments. Verify that path leaves the field editable.
        $container = self::getContainer();

        if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) !== 'saas') {
            $gateId = 'test.' . FeatureGate::class;
            self::assertTrue($container->has($gateId));
            self::assertInstanceOf(NoopFeatureGate::class, $container->get($gateId));
        }

        $client = new Client();
        $client->setName('Acme Corp');

        $form = $this->factory()->create(ClientType::class, $client);

        $field = $form->get('currencyCode');
        self::assertFalse($field->isDisabled());
        self::assertNull($field->getConfig()->getOption('feature_gated'));

        $form->submit([
            'name' => $client->getName(),
            'currencyCode' => 'GBP',
            'contacts' => [],
            'addresses' => [],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertSame('GBP', $client->getCurrencyCode());
    }

    private function factory(): FormFactoryInterface
    {
        $factory = self::getContainer()->get('form.factory');
        self::assertInstanceOf(FormFactoryInterface::class, $factory);

        return $factory;
    }
}
