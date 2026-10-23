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

use Doctrine\Persistence\ManagerRegistry;
use Override;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\SignupMethod;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[Group('functional')]
final class DataLayerEventsTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    private const string EMAIL = 'datalayer@solidworx.co';

    /**
     * @var list<string>
     */
    private array $envOverrides = [];

    #[After]
    public function resetEnvOverrides(): void
    {
        foreach ($this->envOverrides as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
        }

        $this->envOverrides = [];
    }

    public function testSignupPushesSignUpOnceAndOnboardingStoresTheHeardAboutAnswer(): void
    {
        $client = $this->bootClient();

        $crawler = $client->request(Request::METHOD_GET, '/register');
        $client->submit($crawler->filter('form')->form([
            'register[email]' => self::EMAIL,
            'register[plainPassword]' => 'Sup3rStr0ngP@ssw0rd',
            'register[acceptTerms]' => '1',
        ]));

        self::assertSame(SignupMethod::Registration, $this->user()->getSignupMethod());

        $crawler = $client->request(Request::METHOD_GET, '/onboarding');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('window.dataLayer.push({"event":"sign_up","method":"registration"})', (string) $client->getResponse()->getContent());

        // A reload in the same session does not count the signup twice.
        $crawler = $client->request(Request::METHOD_GET, '/onboarding');
        self::assertStringNotContainsString('"sign_up"', (string) $client->getResponse()->getContent());

        $client->submit($crawler->filter('form')->form([
            'company_setup_step[companyName]' => 'Acme',
            'company_setup_step[companyCurrency]' => 'USD',
            'company_setup_step[heardAbout]' => 'other',
            'company_setup_step[heardAboutOther]' => '<b>A podcast</b>',
        ]));
        self::assertResponseRedirects();

        $client->followRedirect();
        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('window.dataLayer.push({"event":"signup_survey","heard_about":"other"})', $content);
        // Free text stays in the database and never reaches the page.
        self::assertStringNotContainsString('A podcast', $content);

        self::assertSame('other', $this->setting(UserSettingType::SignupHeardAbout));
        self::assertSame('<b>A podcast</b>', $this->setting(UserSettingType::SignupHeardAboutOther));
    }

    public function testSkippingTheQuestionStoresNothing(): void
    {
        $client = $this->bootClient();
        $this->registerAndOnboard($client);

        self::assertNull($this->setting(UserSettingType::SignupHeardAbout));
    }

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    private function registerAndOnboard(KernelBrowser $client): void
    {
        $crawler = $client->request(Request::METHOD_GET, '/register');
        $client->submit($crawler->filter('form')->form([
            'register[email]' => self::EMAIL,
            'register[plainPassword]' => 'Sup3rStr0ngP@ssw0rd',
            'register[acceptTerms]' => '1',
        ]));

        $crawler = $client->request(Request::METHOD_GET, '/onboarding');
        $client->submit($crawler->filter('form')->form([
            'company_setup_step[companyName]' => 'Acme',
            'company_setup_step[companyCurrency]' => 'USD',
        ]));
        self::assertResponseRedirects();
    }

    private function user(): User
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $registry->getManager()->clear();
        $user = $registry->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function setting(UserSettingType $key): ?string
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $setting = $registry->getRepository(UserSetting::class)->findOneBy(['user' => $this->user(), 'key' => $key]);

        return $setting instanceof UserSetting ? $setting->getValue() : null;
    }

    private function bootClient(): KernelBrowser
    {
        foreach (['SOLIDINVOICE_PLATFORM' => 'saas', 'SOLIDINVOICE_ALLOW_REGISTRATION' => '1'] as $name => $value) {
            $_SERVER[$name] = $_ENV[$name] = $value;
            $this->envOverrides[] = $name;
        }

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        return $client;
    }
}
