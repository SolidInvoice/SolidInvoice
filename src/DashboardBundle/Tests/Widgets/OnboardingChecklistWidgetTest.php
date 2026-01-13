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

namespace SolidInvoice\DashboardBundle\Tests\Widgets;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\DashboardBundle\Widgets\OnboardingChecklistWidget;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Zenstruck\Foundry\Test\Factories;

final class OnboardingChecklistWidgetTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGetDataReturnsShowFalseWhenNoUserIsLoggedIn(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $widget = new OnboardingChecklistWidget($manager, $security);

        $data = $widget->getData();

        self::assertArrayHasKey('show', $data);
        self::assertFalse($data['show']);
    }

    public function testGetDataReturnsShowFalseWhenChecklistIsDismissed(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $manager = self::getContainer()->get(ChecklistManager::class);
        $manager->dismiss($user);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $widget = new OnboardingChecklistWidget($manager, $security);
        $data = $widget->getData();

        self::assertArrayHasKey('show', $data);
        self::assertFalse($data['show']);
    }

    public function testGetDataReturnsProgressWhenChecklistShouldBeShown(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $widget = new OnboardingChecklistWidget($manager, $security);

        $data = $widget->getData();

        self::assertArrayHasKey('show', $data);
        self::assertTrue($data['show']);
        self::assertArrayHasKey('progress', $data);
        self::assertIsObject($data['progress']);
    }

    public function testGetTemplateReturnsCorrectTemplatePath(): void
    {
        $security = $this->createMock(Security::class);
        $manager = self::getContainer()->get(ChecklistManager::class);

        $widget = new OnboardingChecklistWidget($manager, $security);

        self::assertSame('@SolidInvoiceDashboard/Widget/onboarding_checklist.html.twig', $widget->getTemplate());
    }
}
