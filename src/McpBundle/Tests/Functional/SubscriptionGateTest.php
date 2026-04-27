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

namespace SolidInvoice\McpBundle\Tests\Functional;

use DateTimeImmutable;
use League\OAuth2\Server\ResourceServer;
use Mockery as M;
use Psr\Http\Message\ServerRequestInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\OAuth\ServerFactoryInterface;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\McpBundle\Security\Attribute as McpAttribute;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies that a denied authorization decision (e.g. expired subscription)
 * blocks MCP authentication with a 403 carrying the voter's reason. This
 * exercises the authorization-checker relay in McpOAuthAuthenticator without
 * coupling the test to any specific voter implementation — exactly the
 * indirection SaasBundle's SubscriptionVoter relies on.
 *
 * @covers \SolidInvoice\McpBundle\Security\McpOAuthAuthenticator
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
        $token = $this->createPersistedToken();

        $authenticator = $this->buildAuthenticator($token, $this->grantingAuthorizationChecker());

        $request = Request::create('/_mcp', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer fake.jwt.token']);
        $authenticator->authenticate($request);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'mcp',
        );

        self::assertNull($response, 'A granted decision should not produce a response.');
    }

    public function testAuthorizationDeniedBlocksRequestWithReason(): void
    {
        $reason = 'Your subscription is currently paused. Reactivate it to continue using this resource.';
        $token = $this->createPersistedToken();

        $authenticator = $this->buildAuthenticator($token, $this->denyingAuthorizationChecker($reason));

        $request = Request::create('/_mcp', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer fake.jwt.token']);
        $authenticator->authenticate($request);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'mcp',
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame('access_denied', $body['error']);
        self::assertSame($reason, $body['error_description']);

        $authenticate = $response->headers->get('WWW-Authenticate');
        self::assertIsString($authenticate);
        self::assertStringContainsString('error="access_denied"', $authenticate);
        self::assertStringContainsString('error_description="' . $reason . '"', $authenticate);
    }

    public function testAuthorizationDeniedWithoutReasonFallsBackToGenericMessage(): void
    {
        $token = $this->createPersistedToken();

        $authenticator = $this->buildAuthenticator($token, $this->denyingAuthorizationChecker(null));

        $request = Request::create('/_mcp', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer fake.jwt.token']);
        $authenticator->authenticate($request);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'mcp',
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame('Access denied.', $body['error_description']);
    }

    private function createPersistedToken(): McpAccessToken
    {
        $container = self::getContainer();

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        self::assertInstanceOf(User::class, $user);

        $clientRepo = $container->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $clientRepo);

        $client = new OAuthClient();
        $client->setName('Subscription Gate Client');
        $client->setRedirectUris(['https://example.com/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $clientRepo->save($client);

        $accessTokenRepo = $container->get(McpAccessTokenRepository::class);
        self::assertInstanceOf(McpAccessTokenRepository::class, $accessTokenRepo);

        $token = new McpAccessToken();
        $token->setOAuthClient($client);
        $token->setUser($user);
        $token->setCompany($this->company);
        $token->setIdentifier('jti-gate-' . bin2hex(random_bytes(8)));
        $token->setScopeValues(['mcp:read']);
        $token->setExpiresAt(new DateTimeImmutable('+1 hour'));
        $accessTokenRepo->persistNewAccessToken($token);

        return $token;
    }

    private function buildAuthenticator(McpAccessToken $token, AuthorizationCheckerInterface $authorizationChecker): McpOAuthAuthenticator
    {
        $container = self::getContainer();

        $accessTokenRepo = $container->get(McpAccessTokenRepository::class);
        self::assertInstanceOf(McpAccessTokenRepository::class, $accessTokenRepo);

        return new McpOAuthAuthenticator(
            $this->buildMockServerFactory($token->getJti(), $token->getUser()->getId()->toRfc4122()),
            $accessTokenRepo,
            $container->get(CompanySelector::class),
            $authorizationChecker,
        );
    }

    private function grantingAuthorizationChecker(): AuthorizationCheckerInterface
    {
        $checker = M::mock(AuthorizationCheckerInterface::class);
        $checker
            ->shouldReceive('isGranted')
            ->with(McpAttribute::ACCESS, null, M::any())
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
            ->with(McpAttribute::ACCESS, null, M::any())
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

    private function buildMockServerFactory(string $jti, string $userId): ServerFactoryInterface
    {
        $validatedRequest = $this->createStub(ServerRequestInterface::class);
        $validatedRequest->method('getAttribute')->willReturnMap([
            ['oauth_access_token_id', null, $jti],
            ['oauth_user_id', null, $userId],
            ['oauth_scopes', null, ['mcp:read']],
        ]);

        $resourceServer = $this->createStub(ResourceServer::class);
        $resourceServer->method('validateAuthenticatedRequest')->willReturn($validatedRequest);

        $factory = $this->createStub(ServerFactoryInterface::class);
        $factory->method('createResourceServer')->willReturn($resourceServer);

        return $factory;
    }
}
