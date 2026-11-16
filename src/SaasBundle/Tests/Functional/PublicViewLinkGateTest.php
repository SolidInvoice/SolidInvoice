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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Action\ViewBilling;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;

/**
 * Verifies the public invoice/quote view link returns 404 when none of the
 * owning company's users has verified their email, and serves the page as
 * soon as one has.
 *
 * Uses the real platform gate with nobody signed in, as on the public page:
 * the gate has to judge each company user it is handed, not the (absent)
 * session user.
 */
#[Group('functional')]
final class PublicViewLinkGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testReturnsNotFoundWhenNoUserIsVerified(): void
    {
        $this->addUser(verified: false);
        $invoice = $this->createInvoice();

        $this->expectException(NotFoundHttpException::class);

        $this->view($invoice);
    }

    public function testServesThePageOnceAUserIsVerified(): void
    {
        $this->addUser(verified: false);
        $this->addUser(verified: true);
        $invoice = $this->createInvoice();

        $response = $this->view($invoice);

        self::assertIsArray($response);
        self::assertArrayHasKey('invoice', $response);
        self::assertSame($invoice->getId()->toString(), $response['invoice']->getId()->toString());
    }

    public function testServesThePageWhenVerificationIsDisabled(): void
    {
        $this->addUser(verified: false);
        $invoice = $this->createInvoice();

        self::assertIsArray($this->view($invoice, verificationEnabled: false));
    }

    private function addUser(bool $verified): void
    {
        // The owning side is User::$companies; keep the in-memory company in step with it.
        $this->company->addUser(UserFactory::createOne(['companies' => [$this->company], 'verified' => $verified]));
    }

    /**
     * @return array<string, mixed>|Response
     */
    private function view(Invoice $invoice, bool $verificationEnabled = true): array | Response
    {
        $container = self::getContainer();

        // Anonymous, like a client opening the link from an email.
        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn(null);

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')
            ->willReturn(false);

        $action = new ViewBilling(
            $container->get('doctrine'),
            $container->get('doctrine')->getRepository(Client::class),
            $authChecker,
            $this->createStub(RouterInterface::class),
            $container->get(CompanySelector::class),
            $container->get(Generator::class),
            $container->get(Environment::class),
            new EmailVerificationGate($security, new IdentityTranslator(), $verificationEnabled),
            $container->get(BillingTemplateResolver::class),
        );

        $uuid = $invoice->getUuid()->toString();

        return $action->invoiceAction(Request::create('/view/invoice/' . $uuid), $uuid);
    }

    private function createInvoice(): Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        return $invoice;
    }
}
