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
use Psr\Clock\ClockInterface;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Affiliate\AffiliateProgram;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('functional')]
final class ReferAndEarnTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testThePageExplainsTheProgram(): void
    {
        $client = $this->loggedInClient();

        $client->request(Request::METHOD_GET, '/profile/refer-and-earn');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.card-title', 'Earn up to $93 a year for every customer you refer');
        self::assertSelectorExists('a[href="/affiliate/join/page"][target="_blank"][rel="noopener"]');
        self::assertSelectorExists('.swp-settings-nav a.active[href="/profile/refer-and-earn"]');
        self::assertSelectorExists('a[href="/profile/refer-and-earn?source=footer"]');
    }

    public function testJoiningGoesToTheConfiguredSignUpUrl(): void
    {
        $client = $this->loggedInClient();

        $client->request(Request::METHOD_GET, '/affiliate/join/page');

        self::assertResponseRedirects('https://billing.solidinvoice.co/affiliates');
    }

    public function testEverythingIsGoneWhenTheProgramIsDisabled(): void
    {
        $client = $this->loggedInClient();
        $container = $client->getContainer();
        $container->set(AffiliateProgram::class, new AffiliateProgram(
            $container->get(UserSettingRepositoryInterface::class),
            $container->get(ChecklistManager::class),
            $container->get(ClockInterface::class),
            false,
            'https://billing.solidinvoice.co/affiliates',
            '',
            '20%',
            '$93',
            3,
        ));
        $client->disableReboot();

        $client->request(Request::METHOD_GET, '/profile/refer-and-earn');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request(Request::METHOD_GET, '/affiliate/join/page');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request(Request::METHOD_GET, '/profile/notifications');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href^="/profile/refer-and-earn"]');
    }

    private function loggedInClient(): KernelBrowser
    {
        $user = UserFactory::createOne(['companies' => [$this->company]]);

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        return $client;
    }
}
