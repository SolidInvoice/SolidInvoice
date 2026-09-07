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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Tests\Telemetry\CollectingMessageBus;
use SolidInvoice\SaasBundle\Action\WelcomeAction;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

#[CoversClass(WelcomeAction::class)]
final class WelcomeActionTest extends TestCase
{
    public function testFreeTrialModeRedirectsToThePlanPicker(): void
    {
        // The welcome page belongs to card-required onboarding only; free-trial
        // mode keeps the plan picker, and never even looks up a subscription.
        $subscriptionProvider = $this->createMock(SubscriptionProviderInterface::class);
        $subscriptionProvider->expects(self::never())->method('getSubscriptionFor');

        $action = $this->action(
            BillingModeFactory::freeTrial(),
            $this->createStub(CompanySelectorInterface::class),
            $this->createStub(CompanyRepository::class),
            $subscriptionProvider,
            new CollectingMessageBus(),
        );

        $action->setContainer($this->routerContainer());

        $response = $action();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/plans', $response->getTargetUrl());
    }

    public function testPaidTrialPendingRendersTheWelcomePageAndEmitsTelemetry(): void
    {
        $bus = new CollectingMessageBus();

        $companySelector = $this->createMock(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn(new Ulid());

        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->method('find')->willReturn(new Company());

        $subscriptionProvider = $this->createMock(SubscriptionProviderInterface::class);
        $subscriptionProvider->method('getSubscriptionFor')->willReturn(
            $this->subscription(SubscriptionStatus::PENDING),
        );

        $action = $this->action(
            BillingModeFactory::paidTrial(),
            $companySelector,
            $companyRepository,
            $subscriptionProvider,
            $bus,
        );

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('@SolidInvoiceSaas/subscription/welcome.html.twig', self::anything())
            ->willReturn('<html></html>');

        $container = new Container();
        $container->set('twig', $twig);

        $action->setContainer($container);

        $response = $action();

        self::assertNotInstanceOf(RedirectResponse::class, $response);
        self::assertSame('<html></html>', (string) $response->getContent());
        self::assertCount(1, $bus->messages);
        self::assertSame('saas_welcome_page_viewed', $bus->messages[0]->payload['event']);
    }

    public function testPaidTrialWithoutSubscriptionRedirectsToTheDashboard(): void
    {
        $companySelector = $this->createMock(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn(null);

        $action = $this->action(
            BillingModeFactory::paidTrial(),
            $companySelector,
            $this->createStub(CompanyRepository::class),
            $this->createStub(SubscriptionProviderInterface::class),
            new CollectingMessageBus(),
        );

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $route): string => '/' . $route,
        );

        $container = new Container();
        $container->set('router', $router);
        $container->set('request_stack', $this->requestStackWithSession());

        $action->setContainer($container);

        $response = $action();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/_dashboard', $response->getTargetUrl());
    }

    public function testPaidTrialActiveSubscriptionRedirectsToBilling(): void
    {
        $companySelector = $this->createMock(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn(new Ulid());

        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->method('find')->willReturn(new Company());

        $subscriptionProvider = $this->createMock(SubscriptionProviderInterface::class);
        $subscriptionProvider->method('getSubscriptionFor')->willReturn(
            $this->subscription(SubscriptionStatus::ACTIVE),
        );

        $action = $this->action(
            BillingModeFactory::paidTrial(),
            $companySelector,
            $companyRepository,
            $subscriptionProvider,
            new CollectingMessageBus(),
        );

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $route): string => '/' . $route,
        );

        $container = new Container();
        $container->set('router', $router);

        $action->setContainer($container);

        $response = $action();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/billing_index', $response->getTargetUrl());
    }

    private function action(
        BillingMode $billingMode,
        CompanySelectorInterface $companySelector,
        CompanyRepository $companyRepository,
        SubscriptionProviderInterface $subscriptionProvider,
        CollectingMessageBus $bus,
    ): WelcomeAction {
        return new WelcomeAction(
            $subscriptionProvider,
            $companyRepository,
            $companySelector,
            $billingMode,
            $this->makeTelemetry($bus),
        );
    }

    private function subscription(SubscriptionStatus $status): Subscription
    {
        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setPlanId('variant-123');
        $plan->setPrice(1000);

        $subscription = new Subscription();
        $subscription->setPlan($plan);
        $subscription->setStatus($status);

        return $subscription;
    }

    private function routerContainer(): Container
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $route): string => match ($route) {
                'saas_subscription_plans' => '/plans',
                default => '/' . $route,
            },
        );

        $container = new Container();
        $container->set('router', $router);

        return $container;
    }

    private function requestStackWithSession(): RequestStack
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack([$request]);

        return $requestStack;
    }

    private function makeTelemetry(CollectingMessageBus $bus): Telemetry
    {
        $vault = $this->createMock(AbstractVault::class);
        $vault->expects(self::never())->method('generateKeys')->willReturn(true);

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
