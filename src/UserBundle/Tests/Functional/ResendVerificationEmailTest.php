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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Controller\Security\ResendVerificationEmail;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
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

        // Self-hosted installs switch verification off, so turn it on the way a hosted one has it.
        $container = self::getContainer();
        $container->set(EmailVerificationGate::class, new EmailVerificationGate(
            $container->get(Security::class),
            $container->get(TranslatorInterface::class),
            true,
        ));

        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'unverified@example.com',
            'verified' => false,
        ]);
        self::assertInstanceOf(User::class, $user);

        $client->loginUser($user);

        return $client;
    }
}
