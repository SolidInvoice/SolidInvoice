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

namespace SolidInvoice\UserBundle\Tests\Functional\Affiliate;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('functional')]
final class DismissPromptTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testAValidTokenDismissesThePromptForGood(): void
    {
        [$client, $user] = $this->eligibleUserClient();

        $crawler = $client->request(Request::METHOD_GET, '/dashboard');
        $token = $crawler->filter('[data-controller="affiliate-prompt"]')->attr('data-affiliate-prompt-token-value');

        $client->request(Request::METHOD_POST, '/affiliate/prompt/dismiss', ['_token' => $token]);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertNotNull($this->dismissedAt($user));

        $client->request(Request::METHOD_GET, '/dashboard');

        self::assertSelectorNotExists('[data-controller="affiliate-prompt"]');
    }

    public function testAnInvalidTokenIsRejected(): void
    {
        [$client, $user] = $this->eligibleUserClient();

        $client->request(Request::METHOD_POST, '/affiliate/prompt/dismiss', ['_token' => 'not-a-token']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertNull($this->dismissedAt($user));
    }

    public function testAnonymousUsersAreSentToLogIn(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->request(Request::METHOD_POST, '/affiliate/prompt/dismiss', ['_token' => 'not-a-token']);

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    /**
     * @return array{KernelBrowser, User}
     */
    private function eligibleUserClient(): array
    {
        $user = UserFactory::createOne(['companies' => [$this->company]]);

        $settings = self::getContainer()->get(UserSettingRepositoryInterface::class);
        $settings->saveSetting($user, UserSettingType::OnboardComplete, 'true');
        $settings->saveSetting($user, UserSettingType::OnboardingCompletedAt, '2020-01-01 00:00:00');
        $settings->saveSetting($user, UserSettingType::OnboardingChecklistDismissed, 'true');

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        return [$client, $user];
    }

    private function dismissedAt(User $user): ?string
    {
        return self::getContainer()->get(UserSettingRepositoryInterface::class)
            ->getSetting($user, UserSettingType::AffiliatePromptDismissedAt)
            ?->getValue();
    }
}
