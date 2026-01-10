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

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidInvoice\UserBundle\Test\Factory\UserInvitationFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class InvitedRegistrationTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use EnsureApplicationInstalled;

    public function testInvitedUserRegistrationFlow(): void
    {
        $invitedEmail = 'invited@example.com';

        $inviter = UserFactory::createOne(['companies' => [$this->company]]);
        $invitation = UserInvitationFactory::createOne([
            'email' => $invitedEmail,
            'company' => $this->company,
            'invitedBy' => $inviter,
        ]);

        $this->browser()
            ->visit('/register?invitation=' . $invitation->getId())
            ->assertSuccessful()
            ->assertSee('Join ' . $this->company->getName())
            ->assertSee('Invited by ' . $inviter->getEmail())
            ->interceptRedirects()
            ->fillField('invited_register[firstName]', 'John')
            ->fillField('invited_register[lastName]', 'Doe')
            ->fillField('invited_register[plainPassword]', 'SecurePassword123!')
            ->fillField('invited_register[acceptTerms]', '1')
            ->click('Accept & Create Account')
            ->assertRedirected()
            ->followRedirect()
        ;

        // Verify user was created with correct data
        $userRepository = self::getContainer()->get(UserRepository::class);
        assert($userRepository instanceof UserRepository);

        $user = $userRepository->findOneBy(['email' => $invitedEmail]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
        self::assertSame($invitedEmail, $user->getEmail());
        self::assertTrue($user->isEnabled());
        self::assertGreaterThan(0, $user->getCompanies()->count());

        // Verify invitation was deleted
        $invitationRepository = self::getContainer()->get(UserInvitationRepository::class);
        assert($invitationRepository instanceof UserInvitationRepository);

        $deletedInvitation = $invitationRepository->find($invitation->getId());
        self::assertNull($deletedInvitation);
    }

    public function testInvitedRegistrationRendersCorrectTemplate(): void
    {
        $invitation = UserInvitationFactory::createOne([
            'company' => $this->company,
            'invitedBy' => UserFactory::createOne(['companies' => [$this->company]]),
        ]);

        $this->browser()
            ->visit('/register?invitation=' . $invitation->getId())
            ->assertSuccessful()
            ->use(function ($browser): void {
                self::assertCount(1, $browser->crawler()->filter('input[name="invited_register[email]"][readonly]'));
                self::assertCount(1, $browser->crawler()->filter('input[name="invited_register[firstName]"]'));
                self::assertCount(1, $browser->crawler()->filter('input[name="invited_register[lastName]"]'));
                self::assertCount(1, $browser->crawler()->filter('input[name="invited_register[plainPassword]"]'));
                self::assertCount(1, $browser->crawler()->filter('input[name="invited_register[acceptTerms]"]'));
                self::assertCount(0, $browser->crawler()->filter('input[name="invited_register[company]"]'));
            })
        ;
    }

    public function testInvalidInvitationReturns404(): void
    {
        $this->browser()
            ->visit('/register?invitation=01HQAAAAAAAAAAAAAAAAAAAA')
            ->assertStatus(404)
        ;
    }

    public function testInvitedRegistrationValidation(): void
    {
        $invitation = UserInvitationFactory::createOne([
            'company' => $this->company,
            'invitedBy' => UserFactory::createOne(['companies' => [$this->company]]),
        ]);

        $this->browser()
            ->visit('/register?invitation=' . $invitation->getId())
            ->assertSuccessful()
            ->interceptRedirects()
            ->fillField('invited_register[firstName]', '')
            ->fillField('invited_register[lastName]', '')
            ->fillField('invited_register[plainPassword]', 'short')
            // Don't check the acceptTerms checkbox to trigger validation error
            ->click('Accept & Create Account')
            ->assertStatus(422)
            ->use(function ($browser): void {
                self::assertGreaterThan(0, $browser->crawler()->filter('.invalid-feedback')->count());
            })
        ;
    }

    public function testEmailIsPrefilled(): void
    {
        $invitedEmail = 'prefilled@example.com';

        $invitation = UserInvitationFactory::createOne([
            'email' => $invitedEmail,
            'company' => $this->company,
            'invitedBy' => UserFactory::createOne(['companies' => [$this->company]]),
        ]);

        $this->browser()
            ->visit('/register?invitation=' . $invitation->getId())
            ->assertSuccessful()
            ->use(function ($browser) use ($invitedEmail): void {
                $value = $browser->crawler()->filter('input[name="invited_register[email]"]')->attr('value');
                self::assertSame($invitedEmail, $value);
            })
        ;
    }
}
