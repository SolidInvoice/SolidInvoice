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

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Tests\Telemetry\CollectingMessageBus;
use SolidInvoice\SaasBundle\Controller\SubscribeController;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
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

    /**
     * @return array{0: RedirectResponse, 1: array<string, list<string|Stringable>>}
     */
    private function invokeController(?Throwable $exception): array
    {
        $paymentIntegration = $this->createMock(PaymentIntegrationInterface::class);

        if ($exception instanceof Throwable) {
            $paymentIntegration->method('checkout')->willThrowException($exception);
        } else {
            $paymentIntegration->method('checkout')->willReturn('https://checkout.lemonsqueezy.com/buy/abc');
        }

        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setPlanId('variant-123');
        $plan->setPrice(1000);

        $subscription = new Subscription();
        $subscription->setPlan($plan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->method('findOneBy')->willReturn($subscription);

        $subscriptionManager = new SubscriptionManager(
            $subscriptionRepository,
            $this->createStub(PlanRepositoryInterface::class),
            $paymentIntegration,
        );

        $companySelector = $this->createMock(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn(new Ulid());

        $companyRepository = $this->createMock(CompanyRepository::class);
        $companyRepository->method('find')->willReturn(new Company());

        $controller = new SubscribeController(
            $subscriptionManager,
            $companyRepository,
            $companySelector,
            $this->createStub(PlanRepositoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
            $this->makeTelemetry(),
        );

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/billing/subscription/activate');
        $request->setSession($session);

        $requestStack = new RequestStack([$request]);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/billing/');

        $user = new User();
        $user->setEmail('test@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

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
        $vault = $this->createMock(AbstractVault::class);
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
}
