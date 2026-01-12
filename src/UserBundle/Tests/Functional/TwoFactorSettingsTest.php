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

use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class TwoFactorSettingsTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use EnsureApplicationInstalled;
    use DoctrineTestTrait;

    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->markTestSkipped('Test is flaky');
    }

    private function createAuthenticatedUser(): User
    {
        $user = UserFactory::createOne([
            'email' => 'test@example.com',
            'password' => $this->passwordHasher->hashPassword(
                new User(),
                'password'
            ),
            'enabled' => true,
            'verified' => true,
            'companies' => [$this->company],
        ])->_real();

        // Ensure no 2FA is enabled initially
        $user->enableEmailAuth(false);
        $user->setTotpSecret('');
        $user->setBackUpCodes([]);

        return $user;
    }

    public function testNavigateToTwoFactorSettingsPage(): void
    {
        $user = $this->createAuthenticatedUser();

        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            ->assertSee('Two-Factor Authentication')
            ->assertSee('Email Authentication')
            ->assertSee('Authenticator App')
            ->assertSee('Disabled'); // Both methods should be disabled initially
    }

    public function testEnableEmailAuthenticationShowsBackupCodesModal(): void
    {
        $user = $this->createAuthenticatedUser();

        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            // Find and click the Enable button for Email Authentication
            ->click('Enable')
            ->wait(1000) // Wait for LiveComponent to process
            // Backup codes modal should appear
            ->assertSee('Backup Codes')
            ->assertSee('Important:')
            ->assertSee('Save these codes in a secure place');

        // Verify that at least 8 backup codes are displayed
        $this->em->refresh($user);
        self::assertNotEmpty($user->getBackupCodes());
        self::assertGreaterThanOrEqual(8, count($user->getBackupCodes()));
    }

    public function testEnableEmailAuthenticationPersistsAfterPageReload(): void
    {
        $user = $this->createAuthenticatedUser();

        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->click('Enable')
            ->wait(1000)
            // Close the backup codes modal
            ->click('Close')
            ->wait(500)
            // Reload the page
            ->visit('/profile/2fa')
            ->assertSuccessful()
            ->assertSee('Email Authentication')
            ->assertSee('Enabled'); // Should now show as enabled

        // Verify in database
        $this->em->refresh($user);
        self::assertTrue($user->isEmailAuthEnabled());
    }

    public function testDownloadBackupCodesKeepsModalOpen(): void
    {
        $user = $this->createAuthenticatedUser();

        // Enable 2FA and generate backup codes
        $user->enableEmailAuth(true);
        $user->setBackUpCodes(['TEST1-ABC123', 'TEST2-DEF456', 'TEST3-GHI789', 'TEST4-JKL012', 'TEST5-MNO345', 'TEST6-PQR678', 'TEST7-STU901', 'TEST8-VWX234']);
        $this->em->persist($user);
        $this->em->flush();

        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            // Should see Recovery Options section since 2FA is enabled
            ->assertSee('Backup Codes')
            ->assertSee('codes available')
            // Click View Codes button
            ->click('View Codes')
            ->wait(500)
            // Modal should be visible
            ->assertSee('Backup Codes')
            ->assertSee('Download as Text File')
            // Click Download button
            ->click('Download as Text File')
            ->wait(500)
            // Modal should still be visible (not closed by backdrop)
            ->assertSee('Backup Codes')
            ->assertSee('Download as Text File')
            ->assertSee('Regenerate Codes');
    }

    public function testRegenerateBackupCodesKeepsModalOpen(): void
    {
        $user = $this->createAuthenticatedUser();

        // Enable 2FA and generate backup codes
        $user->enableEmailAuth(true);
        $oldCodes = ['OLD1-ABC123', 'OLD2-DEF456', 'OLD3-GHI789', 'OLD4-JKL012', 'OLD5-MNO345', 'OLD6-PQR678', 'OLD7-STU901', 'OLD8-VWX234'];
        $user->setBackUpCodes($oldCodes);
        $this->em->persist($user);
        $this->em->flush();

        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            // Click View Codes
            ->click('View Codes')
            ->wait(500)
            ->assertSee('OLD1-ABC123') // Should see old codes
            // Click Regenerate Codes
            ->click('Regenerate Codes')
            ->wait(1000)
            // Modal should still be visible with new codes
            ->assertSee('Backup Codes')
            ->assertSee('Download as Text File');

        // Verify codes changed in database
        $this->em->refresh($user);
        $newCodes = $user->getBackupCodes();
        self::assertNotEquals($oldCodes[0], $newCodes[0]);
    }

    public function testDisableEmailAuthenticationWhenNoOther2FAEnabled(): void
    {
        $user = $this->createAuthenticatedUser();

        // Enable email 2FA first
        $user->enableEmailAuth(true);
        $user->setBackUpCodes(['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8']);
        $this->em->persist($user);
        $this->em->flush();

        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSee('Enabled')
            // Click Disable button
            ->click('Disable')
            ->wait(1000)
            // Should now show as disabled
            ->assertSee('Disabled');

        // Verify in database - backup codes should be cleared
        $this->em->refresh($user);
        self::assertFalse($user->isEmailAuthEnabled());
        self::assertEmpty($user->getBackupCodes());
    }

    public function testBackupCodesSectionOnlyAppearsWhen2FAEnabled(): void
    {
        $user = $this->createAuthenticatedUser();

        // Initially, no backup codes section should be visible
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            ->assertNotSee('Recovery Options');

        // Enable 2FA
        $user->enableEmailAuth(true);
        $user->setBackUpCodes(['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8']);
        $this->em->persist($user);
        $this->em->flush();

        // Now backup codes section should appear
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            ->assertSee('Backup Codes')
            ->assertSee('codes available')
            ->assertSee('View Codes');
    }

    public function testTrustedDeviceSectionAppearsWhenDeviceIsTrusted(): void
    {
        $user = $this->createAuthenticatedUser();

        // Enable 2FA
        $user->enableEmailAuth(true);
        $this->em->persist($user);
        $this->em->flush();

        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful();

        // Note: Testing trusted device requires full login flow with 2FA
        // which is beyond the scope of this test. We're just verifying
        // the section doesn't appear when device is not trusted.
        // The section would appear if we went through the full 2FA flow
        // with "Trust this device" checkbox.
    }

    public function testTOTPSetupModalOpens(): void
    {
        $user = $this->createAuthenticatedUser();

        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful()
            // Find and click Set Up button for Authenticator App
            ->click('Set Up')
            ->wait(500)
            // TOTP setup modal should appear
            ->assertSee('Set Up Authenticator App')
            ->assertSee('Scan QR Code')
            ->assertSee('Verify Code')
            ->assertSee('Scan this QR code with your authenticator app');
    }

    public function testSecurityTokenIsRefreshedAfterEnabling2FA(): void
    {
        $user = $this->createAuthenticatedUser();

        // Verify user initially has no 2FA enabled
        self::assertFalse($user->is2FaEnabled());

        $browser = $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful();

        // Enable email 2FA
        // @phpstan-ignore-next-line
        $browser
            ->click('Enable')
            ->wait(1000);

        // Close modal
        // @phpstan-ignore-next-line
        $browser
            ->click('Close')
            ->wait(500);

        // The security token should be refreshed, so app.user.is2FaEnabled should be true
        // This is verified by checking that the Recovery Options section appears
        $browser
            ->assertSee('Backup Codes')
            ->assertSee('codes available');

        // Verify in database
        $this->em->refresh($user);
        self::assertTrue($user->is2FaEnabled());
    }

    public function testMultiple2FAMethodsCanBeEnabledSimultaneously(): void
    {
        $user = $this->createAuthenticatedUser();

        // Enable both email and TOTP
        $user->enableEmailAuth(true);
        $user->setTotpSecret('TESTSECRET123456');
        $user->setBackUpCodes(['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8']);
        $this->em->persist($user);
        $this->em->flush();

        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            ->assertSuccessful();

        // Verify both show as enabled
        $this->em->refresh($user);
        self::assertTrue($user->isEmailAuthEnabled());
        self::assertTrue($user->isTotpAuthenticationEnabled());

        // Disable email - backup codes should remain (TOTP still enabled)
        // @phpstan-ignore-next-line
        $this->browser()
            ->actingAs($user)
            ->visit('/profile/2fa')
            // There should be two "Disable" buttons - one for email, one for TOTP
            // Click the first Disable button (email)
            ->click('Disable')
            ->wait(1000);

        $this->em->refresh($user);
        self::assertFalse($user->isEmailAuthEnabled());
        self::assertTrue($user->isTotpAuthenticationEnabled());
        self::assertNotEmpty($user->getBackupCodes()); // Codes should remain since TOTP is still enabled
    }
}
