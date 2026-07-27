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

namespace SolidInvoice\SaasBundle\Tests\Controller;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use RuntimeException;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Tests\Telemetry\CollectingMessageBus;
use SolidInvoice\SaasBundle\Controller\SubscribeController;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Integration\Options;
use SolidWorx\Platform\SaasBundle\Integration\PaymentIntegrationInterface;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Stringable;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

#[CoversClass(SubscribeController::class)]
final class SubscribeControllerTest extends TestCase
{
    private CollectingMessageBus $bus;

    protected function setUp(): void
    {
        $this->bus = new CollectingMessageBus();
    }

    public function testTransportExceptionRedirectsToOverviewWithErrorFlash(): void
    {
        $exception = new TransportException('HTTP/2 404 returned for "https://api.lemonsqueezy.com/v1/checkouts"');

        $response = $this->invokeController($exception);

        self::assertInstanceOf(RedirectResponse::class, $response[0]);
        self::assertSame('/billing/', $response[0]->getTargetUrl());
        self::assertArrayHasKey('error', $response[1]);
        self::assertNotEmpty($response[1]['error']);

        self::assertCount(1, $this->bus->messages);
        self::assertSame('saas_checkout_failed', $this->bus->messages[0]->payload['event']);
        self::assertSame('pro', $this->bus->messages[0]->payload['properties']['plan']);
    }

    public function testHttpClientExceptionRedirectsToOverviewWithErrorFlash(): void
    {
        $exception = new class() extends RuntimeException implements ClientExceptionInterface {
            public function getResponse(): ResponseInterface
            {
                throw new LogicException('Not needed in test');
            }
        };

        $response = $this->invokeController($exception);

        self::assertInstanceOf(RedirectResponse::class, $response[0]);
        self::assertSame('/billing/', $response[0]->getTargetUrl());
        self::assertArrayHasKey('error', $response[1]);
        self::assertNotEmpty($response[1]['error']);

        self::assertCount(1, $this->bus->messages);
        self::assertSame('saas_checkout_failed', $this->bus->messages[0]->payload['event']);
    }

    public function testSuccessfulCheckoutEmitsCheckoutStartedTelemetryAndRedirects(): void
    {
        $response = $this->invokeController(null);

        self::assertInstanceOf(RedirectResponse::class, $response[0]);
        self::assertSame('https://checkout.lemonsqueezy.com/buy/abc', $response[0]->getTargetUrl());

        self::assertCount(1, $this->bus->messages);
        self::assertSame('event', $this->bus->messages[0]->type);
        self::assertSame('saas_checkout_started', $this->bus->messages[0]->payload['event']);
        self::assertSame('pro', $this->bus->messages[0]->payload['properties']['plan']);
    }

    public function testActiveTrialCheckoutGrantsLemonSqueezyTrial(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::TRIAL,
            CarbonImmutable::parse('2024-01-10'),
        );

        $options = $this->captureCheckoutOptions($subscription, CarbonImmutable::parse('2024-01-01'));

        self::assertFalse($options->getValue(Options::SKIP_TRIAL));
    }

    public function testExpiredTrialCheckoutSkipsTrial(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::TRIAL,
            CarbonImmutable::parse('2023-12-30'),
        );

        $options = $this->captureCheckoutOptions($subscription, CarbonImmutable::parse('2024-01-01'));

        self::assertTrue($options->getValue(Options::SKIP_TRIAL));
    }

    public function testExternallyBilledTrialSkipsTrial(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::TRIAL,
            CarbonImmutable::parse('2024-01-10'),
            'ls_existing_1',
        );

        $options = $this->captureCheckoutOptions($subscription, CarbonImmutable::parse('2024-01-01'));

        self::assertTrue($options->getValue(Options::SKIP_TRIAL));
    }

    public function testCancelledCheckoutSkipsTrial(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::CANCELLED,
            CarbonImmutable::parse('2024-01-10'),
        );

        $options = $this->captureCheckoutOptions($subscription, CarbonImmutable::parse('2024-01-01'));

        self::assertTrue($options->getValue(Options::SKIP_TRIAL));
    }

    /**
     * The whole point of paid-trial mode: checkout is how the trial starts, so
     * the first checkout must let Lemon Squeezy apply the variant's trial. The
     * subscription is still PENDING here, which under the free-trial rules
     * would have skipped the trial and charged the user immediately.
     */
    public function testPendingCheckoutGrantsTrialInPaidTrialMode(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::PENDING,
            CarbonImmutable::parse('2024-01-01'),
        );

        $options = $this->captureCheckoutOptions(
            $subscription,
            CarbonImmutable::parse('2024-01-01'),
            BillingModeFactory::paidTrial(),
        );

        self::assertFalse($options->getValue(Options::SKIP_TRIAL));
    }

    public function testExternallyBilledCheckoutSkipsTrialInPaidTrialMode(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::ACTIVE,
            CarbonImmutable::parse('2024-02-01'),
            'ls_existing_1',
        );

        $options = $this->captureCheckoutOptions(
            $subscription,
            CarbonImmutable::parse('2024-01-01'),
            BillingModeFactory::paidTrial(),
        );

        self::assertTrue($options->getValue(Options::SKIP_TRIAL));
    }

    /**
     * Free-trial mode must be unaffected by the paid-trial branch: a PENDING
     * subscription there is a free-plan upgrade and is charged immediately.
     */
    public function testPendingCheckoutSkipsTrialInFreeTrialMode(): void
    {
        $subscription = $this->trialSubscription(
            SubscriptionStatus::PENDING,
            CarbonImmutable::parse('2024-01-01'),
        );

        $options = $this->captureCheckoutOptions($subscription, CarbonImmutable::parse('2024-01-01'));

        self::assertTrue($options->getValue(Options::SKIP_TRIAL));
    }

    /**
     * @return array{0: RedirectResponse, 1: array<string, list<string|Stringable>>}
     */
    private function invokeController(?Throwable $exception): array
    {
        $paymentIntegration = $this->createMock(PaymentIntegrationInterface::class);

        if ($exception instanceof Throwable) {
            $paymentIntegration->expects(self::once())->method('checkout')->willThrowException($exception);
        } else {
            $paymentIntegration->expects(self::once())->method('checkout')->willReturn('https://checkout.lemonsqueezy.com/buy/abc');
        }

        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setPlanId('variant-123');
        $plan->setPrice(1000);

        $subscription = new Subscription();
        $subscription->setPlan($plan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::once())->method('findOneBy')->willReturn($subscription);

        $subscriptionManager = new SubscriptionManager(
            $subscriptionRepository,
            $this->createStub(PlanRepositoryInterface::class),
            $paymentIntegration,
        );

        $companySelector = $this->createMock(CompanySelectorInterface::class);
        $companySelector->expects(self::once())->method('getCompany')->willReturn(new Ulid());

        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->expects(self::once())->method('find')->willReturn(new Company());

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(CarbonImmutable::parse('2024-01-01'));

        $controller = new SubscribeController(
            $subscriptionManager,
            $companyRepository,
            $companySelector,
            $this->createStub(PlanRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
            $this->makeTelemetry(),
            $clock,
            BillingModeFactory::freeTrial(),
        );

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/billing/subscription/activate');
        $request->setSession($session);

        $requestStack = new RequestStack([$request]);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/billing/');

        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())->method('getToken')->willReturn($token);

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('router', $router);
        $container->set('security.token_storage', $tokenStorage);

        $controller->setContainer($container);

        /** @var RedirectResponse $response */
        $response = $controller(new Request());

        return [$response, $session->getFlashBag()->all()];
    }

    private function makeTelemetry(): Telemetry
    {
        $vault = $this->createStub(AbstractVault::class);
        $vault->method('generateKeys')->willReturn(true);

        return new Telemetry(
            $this->bus,
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

    private function trialSubscription(
        SubscriptionStatus $status,
        DateTimeImmutable $endDate,
        ?string $subscriptionId = null,
    ): Subscription {
        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setPlanId('variant-123');
        $plan->setPrice(1000);

        $subscription = new Subscription();
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(CarbonImmutable::parse('2024-01-01'));
        $subscription->setEndDate($endDate);
        $subscription->setSubscriptionId($subscriptionId);

        return $subscription;
    }

    private function captureCheckoutOptions(
        Subscription $subscription,
        DateTimeImmutable $now,
        ?BillingMode $billingMode = null,
    ): Options {
        $capturedOptions = null;

        $paymentIntegration = $this->createMock(PaymentIntegrationInterface::class);
        $paymentIntegration->expects(self::once())->method('checkout')->willReturnCallback(
            static function (Subscription $s, ?Options $options) use (&$capturedOptions): string {
                $capturedOptions = $options;

                return 'https://checkout.lemonsqueezy.com/buy/abc';
            }
        );

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::once())->method('findOneBy')->willReturn($subscription);

        $subscriptionManager = new SubscriptionManager(
            $subscriptionRepository,
            $this->createStub(PlanRepositoryInterface::class),
            $paymentIntegration,
        );

        $companySelector = $this->createMock(CompanySelectorInterface::class);
        $companySelector->expects(self::once())->method('getCompany')->willReturn(new Ulid());

        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->expects(self::once())->method('find')->willReturn(new Company());

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        $controller = new SubscribeController(
            $subscriptionManager,
            $companyRepository,
            $companySelector,
            $this->createStub(PlanRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
            $this->makeTelemetry(),
            $clock,
            $billingMode ?? BillingModeFactory::freeTrial(),
        );

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/billing/subscription/activate');
        $request->setSession($session);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/billing/');

        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())->method('getToken')->willReturn($token);

        $container = new Container();
        $container->set('request_stack', new RequestStack([$request]));
        $container->set('router', $router);
        $container->set('security.token_storage', $tokenStorage);

        $controller->setContainer($container);
        $controller(new Request());

        self::assertInstanceOf(Options::class, $capturedOptions);

        return $capturedOptions;
    }
}
