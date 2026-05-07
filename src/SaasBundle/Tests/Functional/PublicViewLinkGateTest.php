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

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Action\ViewBilling;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the public invoice/quote view link returns 404 when the owning
 * company is gated by the SaaS email-verification gate, and serves the page
 * when the gate is open.
 *
 * @group functional
 */
final class PublicViewLinkGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testReturnsNotFoundWhenCompanyGated(): void
    {
        $action = $this->buildAction(gated: true);

        $invoice = $this->createInvoice();

        $this->expectException(NotFoundHttpException::class);

        $action->invoiceAction(Request::create('/view/invoice/' . $invoice->getUuid()->toString()), $invoice->getUuid()->toString());
    }

    public function testReturnsArrayWhenCompanyNotGated(): void
    {
        $action = $this->buildAction(gated: false);

        $invoice = $this->createInvoice();

        $response = $action->invoiceAction(Request::create('/view/invoice/' . $invoice->getUuid()->toString()), $invoice->getUuid()->toString());

        self::assertIsArray($response);
        self::assertArrayHasKey('invoice', $response);
        self::assertSame($invoice->getId()->toString(), $response['invoice']->getId()->toString());
    }

    private function buildAction(bool $gated): ViewBilling
    {
        $container = self::getContainer();

        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isCompanyGated')->willReturn($gated);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(false);

        $router = $this->createMock(RouterInterface::class);

        return new ViewBilling(
            $container->get('doctrine'),
            $authChecker,
            $router,
            $container->get(CompanySelector::class),
            $container->get(Generator::class),
            $container->get(Environment::class),
            $gate,
        );
    }

    private function createInvoice(): \SolidInvoice\InvoiceBundle\Entity\Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        return $invoice->_real();
    }
}
