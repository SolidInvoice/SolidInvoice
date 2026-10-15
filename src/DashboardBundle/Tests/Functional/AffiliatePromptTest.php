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

namespace SolidInvoice\DashboardBundle\Tests\Functional;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[Group('functional')]
final class AffiliatePromptTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testAnEligibleUserGetsThePrompt(): void
    {
        $client = $this->dashboardClient(onboardedAt: '2020-01-01 00:00:00');

        $client->request(Request::METHOD_GET, '/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.toast[data-controller="affiliate-prompt"]');
        self::assertSelectorExists('.toast a[href="/affiliate/join/banner"][target="_blank"][rel="noopener"]');
        self::assertSelectorExists('a[href="/profile/refer-and-earn?source=dashboard"]');
    }

    public function testARecentlyOnboardedUserOnlyGetsTheQuietCard(): void
    {
        $client = $this->dashboardClient(onboardedAt: Carbon::now()->format('Y-m-d H:i:s'));

        $client->request(Request::METHOD_GET, '/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-controller="affiliate-prompt"]');
        self::assertSelectorExists('a[href="/profile/refer-and-earn?source=dashboard"]');
    }

    private function dashboardClient(string $onboardedAt): KernelBrowser
    {
        $user = UserFactory::createOne(['companies' => [$this->company]]);

        $settings = self::getContainer()->get(UserSettingRepositoryInterface::class);
        $settings->saveSetting($user, UserSettingType::OnboardComplete, 'true');
        $settings->saveSetting($user, UserSettingType::OnboardingCompletedAt, $onboardedAt);
        $settings->saveSetting($user, UserSettingType::OnboardingChecklistDismissed, 'true');

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        return $client;
    }
}
