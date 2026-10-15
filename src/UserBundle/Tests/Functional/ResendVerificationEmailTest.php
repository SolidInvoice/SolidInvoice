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

namespace SolidInvoice\UserBundle\Tests\Functional;

use Override;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\Test\EmailVerificationKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Controller\Security\ResendVerificationEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * The banner SolidInvoice puts on every page for an unverified hosted user, and the resend button
 * on it, driven through the real kernel.
 */
#[Group('functional')]
final class ResendVerificationEmailTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use InteractsWithMailer;

    #[Override]
    protected static function getKernelClass(): string
    {
        return EmailVerificationKernel::class;
    }

    public function testTheBannerCarriesTheSolidInvoiceMessageAndAResendButton(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_GET, '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-banner', 'sending invoices, activating recurring invoices');
        self::assertCount(2, $client->getCrawler()->filter('form[action="' . ResendVerificationEmail::PATH . '"]'), 'Expected the button on the banner and on the profile page.');
    }

    public function testTheResendButtonEmailsANewLinkAndReturnsToThePage(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_GET, '/profile');
        $client->submit($client->getCrawler()->filter('.alert-banner form')->form());

        self::assertResponseRedirects('http://localhost/profile');
        $this->mailer()->assertSentEmailCount(1);
        $this->mailer()->assertEmailSentTo('unverified@example.com', 'Please Confirm your Email');
    }

    public function testTheResendEndpointRejectsAMissingCsrfToken(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_POST, ResendVerificationEmail::PATH);

        self::assertResponseRedirects('/');
        $this->mailer()->assertNoEmailSent();
    }

    public function testTheResendEndpointOnlyAcceptsPost(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_GET, ResendVerificationEmail::PATH);

        self::assertResponseStatusCodeSame(405);
    }

    private function bootClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'unverified@example.com',
            'verified' => false,
        ]);
        self::assertInstanceOf(User::class, $user);

        // Creating an unverified user emails it a link; only the ones the tests send count.
        $this->mailer()->reset();

        $client->loginUser($user);

        return $client;
    }
}
