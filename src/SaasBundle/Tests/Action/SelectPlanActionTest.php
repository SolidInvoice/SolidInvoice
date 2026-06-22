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

namespace SolidInvoice\SaasBundle\Tests\Action;

use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Tests\Telemetry\CollectingMessageBus;
use SolidInvoice\SaasBundle\Action\SelectPlanAction;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;

#[CoversClass(SelectPlanAction::class)]
final class SelectPlanActionTest extends TestCase
{
    public function testEmitsPricingPageViewedTelemetryWhenPricingPageRenders(): void
    {
        $bus = new CollectingMessageBus();

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->method('findAllOrdered')->willReturn([
            $this->makePlan('Free'),
            $this->makePlan('Solo'),
        ]);

        $subscriptionProvider = $this->createMock(SubscriptionProviderInterface::class);
        $subscriptionProvider->method('getSubscriptionFor')->willReturn(null);

        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->method('find')->willReturn(new Company());

        // CompanySelector is final; a real instance with no selected company
        // returns null from getCompany(), which short-circuits subscription
        // lookup and lets the action fall through to the pricing render.
        $companySelector = new CompanySelector($this->createStub(ManagerRegistry::class));

        $action = new SelectPlanAction(
            $planRepository,
            $subscriptionProvider,
            $companyRepository,
            $companySelector,
            $this->makeTelemetry($bus),
        );

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<html></html>');

        $container = new Container();
        $container->set('twig', $twig);

        $action->setContainer($container);

        $action();

        self::assertCount(1, $bus->messages);
        self::assertSame('event', $bus->messages[0]->type);
        self::assertSame('saas_pricing_page_viewed', $bus->messages[0]->payload['event']);
    }

    private function makePlan(string $name): Plan
    {
        return new Plan()->setName($name);
    }

    private function makeTelemetry(CollectingMessageBus $bus): Telemetry
    {
        $vault = $this->createMock(AbstractVault::class);
        $vault->method('generateKeys')->willReturn(true);

        return new Telemetry(
            $bus,
            new ConfigWriter($vault, '/tmp/solidinvoice-test-config'),
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            'build-123',
            true,
            'manual',
            false,
            'en',
            null,
        );
    }
}
