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

namespace SolidInvoice\UserBundle\Tests\Twig\Components;

use Doctrine\Persistence\ObjectManager;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\UserBundle\Twig\Components\TwoFactorSettings;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use function preg_replace;

final class TwoFactorSettingsTest extends LiveComponentTest
{
    private TestLiveComponent $component;

    private ObjectManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        $this->ensureSessionIsSet();

        // Reset user's 2FA state before each test
        $user = $this->getUser();
        $user->enableEmailAuth(false);
        $user->setTotpSecret('');
        $user->setBackUpCodes([]);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);
    }

    public function testRenderComponentWithNo2FAEnabled(): void
    {
        $html = $this->component->render()->toString();

        $this->assertMatchesHtmlSnapshot(
            $this->replaceQrCodeDataUri(
                $this->replaceChecksum($this->replaceUuid($html))
            )
        );
    }

    public function testRenderComponentWithEmailAuthEnabled(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $user->setBackUpCodes(['CODE1-ABC123', 'CODE2-DEF456', 'CODE3-GHI789', 'CODE4-JKL012', 'CODE5-MNO345', 'CODE6-PQR678', 'CODE7-STU901', 'CODE8-VWX234']);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $html = $this->component->render()->toString();

        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testRenderComponentWithTOTPEnabled(): void
    {
        $user = $this->getUser();
        $user->setTotpSecret('TESTSECRET123456');
        $user->setBackUpCodes(['CODE1-ABC123', 'CODE2-DEF456', 'CODE3-GHI789', 'CODE4-JKL012', 'CODE5-MNO345', 'CODE6-PQR678', 'CODE7-STU901', 'CODE8-VWX234']);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $html = $this->component->render()->toString();

        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testRenderComponentWithBoth2FAMethodsEnabled(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $user->setTotpSecret('TESTSECRET123456');
        $user->setBackUpCodes(['CODE1-ABC123', 'CODE2-DEF456', 'CODE3-GHI789', 'CODE4-JKL012', 'CODE5-MNO345', 'CODE6-PQR678', 'CODE7-STU901', 'CODE8-VWX234']);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $html = $this->component->render()->toString();

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($html)));
    }

    public function testEnableEmailAuthGeneratesBackupCodesWhenNoneExist(): void
    {
        $user = $this->getUser();
        self::assertFalse($user->isEmailAuthEnabled());
        self::assertEmpty($user->getBackupCodes());

        $this->component->call('enableEmailAuth');

        $user = $this->getUser();

        self::assertTrue($user->isEmailAuthEnabled());
        self::assertNotEmpty($user->getBackupCodes());
        self::assertGreaterThanOrEqual(8, count($user->getBackupCodes()));

        // Verify component renders with backup codes modal visible
        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot(
            $this->replaceBackupCodes(
                $this->replaceDateTimeStamp(
                    $this->replaceQrCodeDataUri(
                        $this->replaceChecksum(
                            $this->replaceUuid($html)
                        )
                    )
                )
            )
        );
    }

    public function testEnableEmailAuthDoesNotGenerateBackupCodesWhenTheyExist(): void
    {
        $user = $this->getUser();
        $existingCodes = ['EXISTING1', 'EXISTING2', 'EXISTING3', 'EXISTING4', 'EXISTING5', 'EXISTING6', 'EXISTING7', 'EXISTING8'];
        $user->setBackUpCodes($existingCodes);

        $this->em->persist($user);
        $this->em->flush();

        $this->component->call('enableEmailAuth');

        $user = $this->getUser();
        self::assertTrue($user->isEmailAuthEnabled());
        self::assertEquals($existingCodes, $user->getBackupCodes());

        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testDisableEmailAuthClearsBackupCodesWhenNo2FaEnabled(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $user->setBackUpCodes(['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8']);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $this->component->call('disableEmailAuth');

        $user = $this->getUser();
        self::assertFalse($user->isEmailAuthEnabled());
        self::assertEmpty($user->getBackupCodes());

        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testDisableEmailAuthKeepsBackupCodesWhenTOTPEnabled(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $user->setTotpSecret('TESTSECRET123456');
        $codes = ['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8'];
        $user->setBackUpCodes($codes);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $this->component->call('disableEmailAuth');

        $user = $this->getUser();
        self::assertFalse($user->isEmailAuthEnabled());
        self::assertTrue($user->isTotpAuthenticationEnabled());
        self::assertEquals($codes, $user->getBackupCodes());

        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testDisableTOTPAuthClearsBackupCodesWhenNo2FaEnabled(): void
    {
        $user = $this->getUser();
        $user->setTotpSecret('TESTSECRET123456');
        $user->setBackUpCodes(['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8']);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $this->component->call('disableTOTPAuth');

        $user = $this->getUser();
        self::assertFalse($user->isTotpAuthenticationEnabled());
        self::assertEmpty($user->getBackupCodes());

        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testDisableTOTPAuthKeepsBackupCodesWhenEmailEnabled(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $user->setTotpSecret('TESTSECRET123456');
        $codes = ['CODE1', 'CODE2', 'CODE3', 'CODE4', 'CODE5', 'CODE6', 'CODE7', 'CODE8'];
        $user->setBackUpCodes($codes);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $this->component->call('disableTOTPAuth');

        $user = $this->getUser();
        self::assertFalse($user->isTotpAuthenticationEnabled());
        self::assertTrue($user->isEmailAuthEnabled());
        self::assertEquals($codes, $user->getBackupCodes());

        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testRegenerateBackupCodesGeneratesNewCodes(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $oldCodes = ['OLD1', 'OLD2', 'OLD3', 'OLD4', 'OLD5', 'OLD6', 'OLD7', 'OLD8'];
        $user->setBackUpCodes($oldCodes);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $this->component->call('regenerateBackupCodes');

        $user = $this->getUser();
        $newCodes = $user->getBackupCodes();
        self::assertNotEmpty($newCodes);
        self::assertGreaterThanOrEqual(8, count($newCodes));
        self::assertNotEquals($oldCodes[0], $newCodes[0]);

        // Verify component renders with backup codes modal visible
        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot(
            $this->replaceBackupCodes(
                $this->replaceDateTimeStamp(
                    $this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html)))
                )
            )
        );
    }

    public function testDownloadBackupCodesKeepsModalOpen(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $codes = ['CODE1-ABC123', 'CODE2-DEF456', 'CODE3-GHI789', 'CODE4-JKL012', 'CODE5-MNO345', 'CODE6-PQR678', 'CODE7-STU901', 'CODE8-VWX234'];
        $user->setBackUpCodes($codes);

        $this->em->persist($user);
        $this->em->flush();

        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        // Set showBackupCodes to true to open modal first
        $this->component->set('showBackupCodes', true);

        $this->component->call('downloadBackupCodes');

        // The showBackupCodes prop should remain true to keep modal open
        $html = $this->component->render()->toString();
        self::assertStringContainsString('download:file', $html);

        $this->assertMatchesHtmlSnapshot($this->replaceBackupCodes($this->replaceDateTimeStamp($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))))));
    }

    public function testShowQrModalRendersSetupModal(): void
    {
        // Set showQrModal to true
        $this->component->set('showQrModal', true);

        $html = $this->component->render()->toString();

        // Normalize QR code data URI before snapshot
        $html = preg_replace('/data:image\/png;base64,[A-Za-z0-9+\/=]+/', 'data:image/png;base64,NORMALIZED_QR_CODE', $html);

        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testShowBackupCodesModalWithCodes(): void
    {
        // Enable 2FA first so backup codes section is rendered
        $user = $this->getUser();
        $user->enableEmailAuth(true);
        $user->setBackUpCodes(['CODE1-ABC123', 'CODE2-DEF456', 'CODE3-GHI789', 'CODE4-JKL012', 'CODE5-MNO345', 'CODE6-PQR678', 'CODE7-STU901', 'CODE8-VWX234']);

        $this->em->persist($user);
        $this->em->flush();

        // Create a new component instance with the updated user state
        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        // Set showBackupCodes to true (open modal)
        $this->component->set('showBackupCodes', true);

        $html = $this->component->render()->toString();

        $this->assertMatchesHtmlSnapshot($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html))));
    }

    public function testIsDeviceTrustedReturnsFalseByDefault(): void
    {
        $html = $this->component->render()->toString();

        // Trusted device section should not be visible by default
        self::assertStringNotContainsString('Trusted Device', $html);
    }

    public function testClearTrustedDeviceClearsTheToken(): void
    {
        // This test would require setting up a trusted device token first
        // For now, we'll just verify the action can be called without errors
        $this->component->call('clearTrustedDevice');

        $html = $this->component->render()->toString();
        self::assertStringContainsString('Email Authentication', $html);
    }

    public function testGetQrContentReturnsDataUri(): void
    {
        $html = $this->component->render()->toString();

        // QR code modal should contain a data URI for the QR code image
        self::assertStringContainsString('data:image/png;base64,', $html);
    }

    public function testSecurityTokenIsRefreshedAfterEnablingEmailAuth(): void
    {
        $user = $this->getUser();
        self::assertFalse($user->isEmailAuthEnabled());

        $this->component->call('enableEmailAuth');

        // After enabling, the user in the security token should be refreshed
        $user = $this->getUser();

        self::assertTrue($user->isEmailAuthEnabled());

        // Verify the rendered output uses the updated state
        $html = $this->component->render()->toString();
        self::assertStringContainsString('bg-success-lt', $html);
    }

    public function testSecurityTokenIsRefreshedAfterDisablingEmailAuth(): void
    {
        $user = $this->getUser();
        $user->enableEmailAuth(true);

        $this->em->persist($user);
        $this->em->flush();

        // Create a new component instance with the updated user state
        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $this->component->call('disableEmailAuth');

        $user = $this->getUser();
        self::assertFalse($user->isEmailAuthEnabled());

        $html = $this->component->render()->toString();
        self::assertStringContainsString('bg-secondary-lt', $html);
    }

    public function testMultiple2FAMethodsCanBeEnabledSimultaneously(): void
    {
        $user = $this->getUser();

        // Enable email auth
        $this->component->call('enableEmailAuth');

        $user = $this->getUser();
        self::assertTrue($user->isEmailAuthEnabled());

        // Enable TOTP by setting secret directly
        $user->setTotpSecret('TESTSECRET123456');
        $this->em->persist($user);
        $this->em->flush();

        // Create a new component instance with both methods enabled
        $this->component = $this->createLiveComponent(
            name: TwoFactorSettings::class,
            client: $this->client,
        )->actingAs($user);

        $html = $this->component->render()->toString();

        // Both should show as enabled
        self::assertTrue($user->isEmailAuthEnabled());
        self::assertTrue($user->isTotpAuthenticationEnabled());

        $this->assertMatchesHtmlSnapshot($this->replaceBackupCodes($this->replaceDateTimeStamp($this->replaceChecksum($this->replaceUuid($html)))));

        // Disable email - backup codes should remain (TOTP still enabled)
        $this->component->call('disableEmailAuth');

        $user = $this->getUser();
        self::assertFalse($user->isEmailAuthEnabled());
        self::assertTrue($user->isTotpAuthenticationEnabled());
        self::assertNotEmpty($user->getBackupCodes());

        $html = $this->component->render()->toString();
        $this->assertMatchesHtmlSnapshot($this->replaceBackupCodes($this->replaceQrCodeDataUri($this->replaceChecksum($this->replaceUuid($html)))));
    }

    private function replaceQrCodeDataUri(string $replaceChecksum): string
    {
        return preg_replace('/data:image\/png;base64,[A-Za-z0-9+\/=]+/', 'data:image/png;base64,NORMALIZED_QR_CODE', $replaceChecksum);
    }

    private function replaceDateTimeStamp(string $replaceQrCodeDataUri): string
    {
        return preg_replace(
            '/\d{4}-\d{2}-\d{2}/',
            'YYYY-MM-DD',
            preg_replace(
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
                'YYYY-MM-DD HH:MM:SS',
                $replaceQrCodeDataUri
            )
        );
    }

    private function replaceBackupCodes(string $content): string
    {
        return preg_replace('/[A-Z0-9]{6}-[A-Z0-9]{6}/', 'CODE-NORMALIZED', $content);
    }
}
