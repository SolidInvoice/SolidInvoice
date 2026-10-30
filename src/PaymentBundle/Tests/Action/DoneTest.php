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

namespace SolidInvoice\PaymentBundle\Tests\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Token;
use Payum\Core\Payum;
use Payum\Core\Security\HttpRequestVerifierInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Action\Done;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\PaymentAction\Request\StatusRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(Done::class)]
final class DoneTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testPaymentCompleteListenersRunForTheInvoiceCompany(): void
    {
        $otherCompany = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'client' => ClientFactory::createOne(['company' => $otherCompany]),
            'company' => $otherCompany,
        ]);

        $payment = new Payment();
        $payment->setInvoice($invoice);

        // Creating a company selects it; the payer arrives with a different one (or none) selected.
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::assertTrue($otherCompany->getId()->equals($this->companySelectedWhenPaymentCompletes($payment)));
    }

    public function testAPaymentWithoutAnInvoiceKeepsTheSelectedCompany(): void
    {
        self::assertTrue($this->company->getId()->equals($this->companySelectedWhenPaymentCompletes(new Payment())));
    }

    private function companySelectedWhenPaymentCompletes(Payment $payment): ?Ulid
    {
        $companySelector = self::getContainer()->get(CompanySelector::class);

        $token = new Token();
        $token->setGatewayName('offline');

        $verifier = $this->createStub(HttpRequestVerifierInterface::class);
        $verifier->method('verify')->willReturn($token);

        $gateway = $this->createStub(GatewayInterface::class);
        $gateway->method('execute')->willReturnCallback(static function (StatusRequest $request) use ($payment): void {
            $request->setModel($payment);
            $request->markCaptured();
        });

        $payum = $this->createStub(Payum::class);
        $payum->method('getHttpRequestVerifier')->willReturn($verifier);
        $payum->method('getGateway')->willReturn($gateway);

        $selectedCompany = null;
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(PaymentEvents::PAYMENT_COMPLETE, static function () use ($companySelector, &$selectedCompany): void {
            $selectedCompany = $companySelector->getCompany();
        });

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/payments');

        $authorization = $this->createStub(AuthorizationCheckerInterface::class);
        $authorization->method('isGranted')->willReturn(true);

        $done = new Done($payum, $router, $dispatcher, $authorization, $companySelector);

        $em = $this->createStub(EntityManagerInterface::class);
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);
        $done->setDoctrine($doctrine);

        $done(Request::create('/payments/done'));

        return $selectedCompany;
    }
}
