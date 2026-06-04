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

namespace SolidInvoice\PaymentBundle\Tests\DependencyInjection;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Extension\ExtensionInterface;
use SolidInvoice\PaymentBundle\PaymentAction\Offline\StatusAction;
use SolidInvoice\PaymentBundle\PaymentAction\PaypalExpress\PaymentDetailsStatusAction;
use SolidInvoice\PaymentBundle\Payum\Extension\UpdatePaymentDetailsExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression test for https://github.com/SolidInvoice/SolidInvoice/issues/2436
 *
 * PayumBundle's ContainerAwareCoreGatewayFactory resolves "@serviceId" strings
 * to real service objects via $container->has() / $container->get(). In Symfony's
 * compiled container, has() returns false for private services, so Payum-tagged
 * action/extension services MUST be public or the gateway builder passes raw
 * strings to Gateway::addAction(), causing a TypeError at runtime.
 */
final class PayumActionServicesPublicTest extends KernelTestCase
{
    public function testOfflineStatusActionIsPubliclyAccessible(): void
    {
        $container = self::getContainer();

        self::assertTrue(
            $container->has(StatusAction::class),
            StatusAction::class . ' must be a public service so ContainerAwareCoreGatewayFactory can resolve it'
        );
        self::assertInstanceOf(ActionInterface::class, $container->get(StatusAction::class));
    }

    public function testPaypalExpressPaymentDetailsStatusActionIsPubliclyAccessible(): void
    {
        $container = self::getContainer();

        self::assertTrue(
            $container->has(PaymentDetailsStatusAction::class),
            PaymentDetailsStatusAction::class . ' must be a public service so ContainerAwareCoreGatewayFactory can resolve it'
        );
        self::assertInstanceOf(ActionInterface::class, $container->get(PaymentDetailsStatusAction::class));
    }

    public function testUpdatePaymentDetailsExtensionIsPubliclyAccessible(): void
    {
        $container = self::getContainer();

        self::assertTrue(
            $container->has(UpdatePaymentDetailsExtension::class),
            UpdatePaymentDetailsExtension::class . ' must be a public service so ContainerAwareCoreGatewayFactory can resolve it'
        );
        self::assertInstanceOf(ExtensionInterface::class, $container->get(UpdatePaymentDetailsExtension::class));
    }
}
