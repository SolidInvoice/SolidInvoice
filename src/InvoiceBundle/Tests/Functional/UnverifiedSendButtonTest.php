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
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\Test\EmailVerificationKernel;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Controller\Security\ResendVerificationEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * An unverified user keeps the Send button, disabled, with the reason and a way to fix it next to it.
 */
#[Group('functional')]
final class UnverifiedSendButtonTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    #[Override]
    protected static function getKernelClass(): string
    {
        return EmailVerificationKernel::class;
    }

    public function testTheEditorShowsSendDisabledWithAResendLink(): void
    {
        $crawler = $this->unverifiedClient()->request(Request::METHOD_GET, '/invoices/create');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('button[data-live-action-param="saveSend"][disabled][aria-describedby="send-gated-hint"]'));
        self::assertCount(1, $crawler->filter('#send-gated-hint button[form="resend-verification-email"]'));
        self::assertCount(1, $crawler->filter('form#resend-verification-email[action="' . ResendVerificationEmail::PATH . '"]'));
    }

    public function testThePublishedInvoicePageShowsSendDisabledAndNoClientLink(): void
    {
        $client = $this->unverifiedClient();
        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']),
            'status' => InvoiceStatus::Pending,
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/invoices/view/' . $invoice->getId());

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('button.btn-hero[disabled][aria-describedby="send-gated-hint"]'));
        self::assertCount(1, $crawler->filter('form#resend-verification-email'));
        // The public page 404s while nobody in the company is verified, so there is no link to copy.
        self::assertCount(0, $crawler->filter('[data-controller="clipboard"]'));
    }

    private function unverifiedClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();
        $client->loginUser(UserFactory::createOne(['companies' => [$this->company], 'verified' => false]));
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        return $client;
    }
}
