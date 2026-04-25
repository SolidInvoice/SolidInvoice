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

namespace SolidInvoice\ApiBundle\Tests\Functional;

use Doctrine\Persistence\ManagerRegistry;
use Mockery as M;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Attribute as ApiAttribute;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies that a denied authorization decision blocks API authentication
 * with a 403 carrying the voter's reason. This exercises the
 * authorization-checker relay in ApiTokenAuthenticator without coupling the
 * test to any specific voter implementation.
 *
 * @covers \SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator
 *
 * @group functional
 */
final class SubscriptionGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testAuthorizationGrantedAllowsRequest(): void
    {
        $token = $this->createApiToken();

        $authenticator = $this->buildAuthenticator($this->grantingAuthorizationChecker());

        $request = Request::create('/api/clients', 'GET', [], [], [], ['HTTP_X-API-TOKEN' => $token->getToken()]);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'api',
        );

        self::assertNull($response, 'A granted decision should not produce a response.');
    }

    public function testAuthorizationDeniedBlocksRequestWithReason(): void
    {
        $reason = 'Your trial has ended. Activate a subscription to continue using this resource.';
        $token = $this->createApiToken();

        $authenticator = $this->buildAuthenticator($this->denyingAuthorizationChecker($reason));

        $request = Request::create('/api/clients', 'GET', [], [], [], ['HTTP_X-API-TOKEN' => $token->getToken()]);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'api',
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame($reason, $body['message']);
    }

    public function testAuthorizationDeniedWithoutReasonFallsBackToGenericMessage(): void
    {
        $token = $this->createApiToken();

        $authenticator = $this->buildAuthenticator($this->denyingAuthorizationChecker(null));

        $request = Request::create('/api/clients', 'GET', [], [], [], ['HTTP_X-API-TOKEN' => $token->getToken()]);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'api',
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame('Access denied.', $body['message']);
    }

    private function createApiToken(): ApiToken
    {
        $container = self::getContainer();

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();

        $manager = $container->get(ApiTokenManager::class);
        self::assertInstanceOf(ApiTokenManager::class, $manager);

        $token = $manager->getOrCreate($user, 'Subscription Gate Test');
        // Bind the token to the active company so the authenticator can switch into it.
        $token->setCompany($this->company);

        $registry = $container->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $registry->getManager()->flush();

        return $token;
    }

    private function buildAuthenticator(AuthorizationCheckerInterface $authorizationChecker): ApiTokenAuthenticator
    {
        $container = self::getContainer();

        return new ApiTokenAuthenticator(
            $container->get(ApiTokenUserProvider::class),
            $container->get('doctrine'),
            $container->get(TranslatorInterface::class),
            $container->get(CompanySelector::class),
            $authorizationChecker,
        );
    }

    private function grantingAuthorizationChecker(): AuthorizationCheckerInterface
    {
        $checker = M::mock(AuthorizationCheckerInterface::class);
        $checker
            ->shouldReceive('isGranted')
            ->with(ApiAttribute::ACCESS, null, M::any())
            ->andReturnUsing(static function (string $attribute, mixed $subject, AccessDecision $decision): bool {
                $decision->isGranted = true;

                return true;
            });

        return $checker;
    }

    private function denyingAuthorizationChecker(?string $reason): AuthorizationCheckerInterface
    {
        $checker = M::mock(AuthorizationCheckerInterface::class);
        $checker
            ->shouldReceive('isGranted')
            ->with(ApiAttribute::ACCESS, null, M::any())
            ->andReturnUsing(static function (string $attribute, mixed $subject, AccessDecision $decision) use ($reason): bool {
                $decision->isGranted = false;

                if ($reason !== null) {
                    $vote = new Vote();
                    $vote->addReason($reason);
                    $decision->votes[] = $vote;
                }

                return false;
            });

        return $checker;
    }
}
