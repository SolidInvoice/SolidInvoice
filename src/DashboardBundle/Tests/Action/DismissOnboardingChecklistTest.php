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

namespace SolidInvoice\DashboardBundle\Tests\Action;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\DashboardBundle\Action\DismissOnboardingChecklist;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Zenstruck\Foundry\Test\Factories;

final class DismissOnboardingChecklistTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testInvokeRedirectsBackToDashboard(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $request->request->set('_token', 'valid_token');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $action = new DismissOnboardingChecklist($manager, $security, $urlGenerator, $csrfTokenManager, $requestStack);
        $response = $action($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertStringEndsWith('/dashboard', $response->getTargetUrl());
    }

    public function testInvokeCallsChecklistManagerDismiss(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(true);

        self::assertFalse($manager->isDismissed($user));

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $request->request->set('_token', 'valid_token');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $action = new DismissOnboardingChecklist($manager, $security, $urlGenerator, $csrfTokenManager, $requestStack);
        $action($request);

        self::assertTrue($manager->isDismissed($user));
    }
}
