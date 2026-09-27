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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

/**
 * Verifies `_send_invoice` only answers POST requests carrying a valid CSRF
 * token, and that the invoice view page renders the Send controls as forms.
 *
 * @see \SolidInvoice\InvoiceBundle\Action\Transition\Send
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\Transition\SendTest
 */
final class SendInvoiceRoutingTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testGetOnSendInvoiceRouteIsMethodNotAllowed(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/invoices/action/send/' . new Ulid());

        self::assertResponseStatusCodeSame(405);
    }

    public function testPendingInvoiceViewRendersHeroSendAsPostForm(): void
    {
        $client = $this->loginClient();

        $invoiceClient = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $invoiceClient, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $invoiceClient,
            'status' => InvoiceStatus::Pending,
            'users' => [$contact],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/invoices/view/' . $invoice->getId());

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[action="/invoices/action/send/' . $invoice->getId() . '"]');
        self::assertGreaterThan(0, $form->count(), 'Expected a POST form targeting _send_invoice on the invoice view page.');
        self::assertSame('post', strtolower((string) $form->attr('method')));
        self::assertNotEmpty($form->filter('input[name="_token"]')->attr('value'));
    }

    public function testDraftInvoiceViewRendersPublishAndSendAsPostForm(): void
    {
        $client = $this->loginClient();

        $invoiceClient = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $invoiceClient, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $invoiceClient,
            'status' => InvoiceStatus::Draft,
            'users' => [$contact],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/invoices/view/' . $invoice->getId());

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[action="/invoices/action/send/' . $invoice->getId() . '"]');
        self::assertGreaterThan(0, $form->count(), 'Expected a POST form targeting _send_invoice on the invoice view page.');
        self::assertSame('post', strtolower((string) $form->attr('method')));
        self::assertNotEmpty($form->filter('input[name="_token"]')->attr('value'));
    }

    public function testPaidInvoiceViewRendersModalConfirmAsPostForm(): void
    {
        $client = $this->loginClient();

        $invoiceClient = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $invoiceClient, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $invoiceClient,
            'status' => InvoiceStatus::Paid,
            'users' => [$contact],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/invoices/view/' . $invoice->getId());

        self::assertResponseIsSuccessful();

        $form = $crawler->filter('#send-paid-invoice-modal form[action="/invoices/action/send/' . $invoice->getId() . '"]');
        self::assertGreaterThan(0, $form->count(), 'Expected a POST form targeting _send_invoice inside the paid-invoice confirm modal.');
        self::assertSame('post', strtolower((string) $form->attr('method')));
        self::assertNotEmpty($form->filter('input[name="_token"]')->attr('value'));
    }

    private function loginClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        // Rebooting the kernel drops the company selected by EnsureApplicationInstalled
        // on the old container. Factories that rely on the auto-fill company listener
        // (such as Client's cascade-persisted Credit) need it reselected here.
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $user = UserFactory::createOne(['companies' => [$this->company]]);
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        return $client;
    }
}
