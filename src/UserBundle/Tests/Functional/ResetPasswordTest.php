<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser\MailerComponent;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\TestEmail;

/**
 * @group functional
 */
final class ResetPasswordTest extends WebTestCase
{
    use HasBrowser;
    use DoctrineTestTrait;
    use InteractsWithMailer;

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = self::getContainer()->get(UserRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();
    }

    public function testPasswordReset(): void
    {
        // Create a test user
        $user = (new User())
            ->setEmail('me@example.com')
            ->setPassword('a-test-password-that-will-be-changed-later');
        $this->em->persist($user);
        $this->em->flush();

        $browser = $this->browser();

        $emailLink = '';

        $browser
            ->withProfiling()
            ->request('GET', '/forgot-password')
            ->assertSuccessful()
            ->assertSeeIn('title', 'Reset your password')
            ->use(static fn (MailerComponent $component) => $component->assertNoEmailSent())
            ->withProfiling()
            ->interceptRedirects()
            ->fillField('reset_password_request_form[email]', 'me@example.com')
            ->click('Reset Password')
            ->use(static function (MailerComponent $component) use (&$emailLink): void {
                $component
                    ->assertSentEmailCount(1)
                    ->assertEmailSentTo('me@example.com', function (TestEmail $email) use (&$emailLink): void {
                        $email
                            ->assertSubject('Your password reset request')
                            ->assertTextContains('A password reset was requested for user me@example.com.')
                            ->assertTextContains('If you did not request your password to be reset, you can safely ignore this message.')
                            ->assertTextContains('To reset your password, please copy and paste the below link in your browser')
                            ->assertTextContains('http://localhost/forgot-password/reset/')
                            ->assertHtmlContains('A password reset was requested for user')
                            ->assertHtmlContains('http://localhost/forgot-password/reset/');

                        self::assertSame(1, preg_match('#(/forgot-password/reset/[a-zA-Z0-9]+)#', $email->getTextBody(), $resetLink));
                        $emailLink = $resetLink[0];
                    });

                $component->sentEmails();
            })
            ->followRedirect()
            ->assertOn('/forgot-password/check')
            ->assertSee('An email has been sent. It contains a link you must click to reset your password')
            ->visit($emailLink)
            ->assertRedirectedTo('/forgot-password/reset')
            ->followRedirect()
            ->assertOn('/forgot-password/reset')
            ->assertSeeIn('title', 'Reset Password')
            ->fillField('change_password_form[plainPassword][first]', 'newStrongPassword')
            ->fillField('change_password_form[plainPassword][second]', 'newStrongPassword')
            ->click('Reset Password')
            ->assertRedirectedTo('/login')
            ->followRedirect()
            ->assertOn('/login')
            ->assertSeeIn('.alert-success', 'Your password has been changed successfully. You can now log in.')
        ;

        $user = $this->userRepository->findOneBy(['email' => 'me@example.com']);

        self::assertInstanceOf(User::class, $user);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($passwordHasher->isPasswordValid($user, 'newStrongPassword'));
    }
}
