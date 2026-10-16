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

use Override;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\Test\EmailVerificationKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * "Email me a test copy" for an unverified user: it reaches only their own inbox, and its link
 * verifies them and brings them back to the invoice with Send enabled.
 */
#[Group('functional')]
final class SendTestCopyTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use InteractsWithMailer;

    private KernelBrowser $browser;

    private User $user;

    private Invoice $invoice;

    #[Override]
    protected static function getKernelClass(): string
    {
        return EmailVerificationKernel::class;
    }

    public function testTheTestCopyGoesOnlyToTheUser(): void
    {
        $this->sendTestCopy();

        $this->mailer()->assertSentEmailCount(1);
        $email = $this->mailer()->sentEmails()->first();
        $email->assertTo('owner@example.com')->assertHasFile('invoice_' . $this->invoice->getInvoiceId() . '.pdf');
        self::assertCount(1, $email->inner()->getTo());
        self::assertSame([], $email->inner()->getCc());
        self::assertSame([], $email->inner()->getBcc());

        $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->find($this->invoice->getId());
        self::assertSame(InvoiceStatus::Draft, $invoice?->getStatus(), 'A test copy must not publish the invoice.');
    }

    public function testTheLinkInTheTestCopyVerifiesTheUserAndReturnsToTheInvoice(): void
    {
        $this->sendTestCopy();

        self::assertSame(1, preg_match('#href="(http://localhost/verify\?[^"]+)"#', (string) $this->mailer()->sentEmails()->first()->inner()->getHtmlBody(), $match));

        $this->browser->request(Request::METHOD_GET, html_entity_decode($match[1]));
        $this->browser->followRedirects();

        $crawler = $this->browser->followRedirect();

        self::assertTrue(self::getContainer()->get('doctrine')->getRepository(User::class)->find($this->user->getId())?->isVerified());
        self::assertSame('/invoices/view/' . $this->invoice->getId(), $this->browser->getRequest()->getPathInfo());
        self::assertCount(1, $crawler->filter('form[action="/invoices/action/send/' . $this->invoice->getId() . '"] button.btn-hero:not([disabled])'));
    }

    private function sendTestCopy(): void
    {
        self::ensureKernelShutdown();
        $this->browser = self::createClient();
        $this->browser->disableReboot();

        $this->user = UserFactory::createOne(['companies' => [$this->company], 'email' => 'owner@example.com', 'verified' => false]);
        $this->browser->loginUser($this->user);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $this->invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Draft,
            'users' => [ContactFactory::createOne(['client' => $client, 'company' => $this->company, 'email' => 'client@example.com'])],
        ]);

        // Creating an unverified user emails it a link; only the test copy counts.
        $this->mailer()->reset();

        $crawler = $this->browser->request(Request::METHOD_GET, '/invoices/view/' . $this->invoice->getId());
        $this->browser->submit($crawler->filter('form[action="/invoices/action/send-test-copy/' . $this->invoice->getId() . '"]')->form());

        self::assertResponseRedirects('/invoices/view/' . $this->invoice->getId());
    }
}
