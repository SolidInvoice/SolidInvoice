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
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\OAuth\ServerFactoryInterface;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies that a token bound to a company the user has since been removed from
 * is rejected at authentication time, even if the token is otherwise valid and
 * not revoked.
 *
 * @covers \SolidInvoice\McpBundle\Security\McpOAuthAuthenticator::authenticate
 *
 * @group functional
 */
final class CompanyRevalidationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testRevokedCompanyAccessRejectsToken(): void
    {
        $container = self::getContainer();

        $registry = $container->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $em = $registry->getManager();

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        self::assertInstanceOf(User::class, $user);

        $clientRepo = $container->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $clientRepo);

        $client = new OAuthClient();
        $client->setName('Revalidation Test Client');
        $client->setRedirectUris(['https://example.com/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $clientRepo->save($client);

        $jti = 'jti-reval-' . bin2hex(random_bytes(8));

        $accessTokenRepo = $container->get(McpAccessTokenRepository::class);
        self::assertInstanceOf(McpAccessTokenRepository::class, $accessTokenRepo);

        $token = new McpAccessToken();
        $token->setOAuthClient($client);
        $token->setUser($user);
        $token->setCompany($this->company);
        $token->setIdentifier($jti);
        $token->setScopeValues(['mcp:read']);
        $token->setExpiresAt(new DateTimeImmutable('+1 hour'));
        $accessTokenRepo->persistNewAccessToken($token);

        // Remove the user from the company — simulates an admin deprovisioning
        // the account after consent was already granted.
        $user->removeCompany($this->company);
        $em->flush();

        $authenticator = new McpOAuthAuthenticator(
            $this->buildMockServerFactory($jti, $user->getId()->toRfc4122()),
            $accessTokenRepo,
            $container->get(CompanySelector::class),
            $container->get(AuthorizationCheckerInterface::class),
        );

        $request = Request::create('/_mcp', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer fake.jwt.token']);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('no longer has access to the company');

        $authenticator->authenticate($request);
    }

    public function testValidCompanyMembershipAuthenticatesSuccessfully(): void
    {
        $container = self::getContainer();

        $user = UserFactory::createOne(['companies' => [$this->company]])->_real();
        self::assertInstanceOf(User::class, $user);

        $clientRepo = $container->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $clientRepo);

        $client = new OAuthClient();
        $client->setName('Revalidation Happy Path Client');
        $client->setRedirectUris(['https://example.com/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');
        $clientRepo->save($client);

        $jti = 'jti-reval-ok-' . bin2hex(random_bytes(8));

        $accessTokenRepo = $container->get(McpAccessTokenRepository::class);
        self::assertInstanceOf(McpAccessTokenRepository::class, $accessTokenRepo);

        $token = new McpAccessToken();
        $token->setOAuthClient($client);
        $token->setUser($user);
        $token->setCompany($this->company);
        $token->setIdentifier($jti);
        $token->setScopeValues(['mcp:read']);
        $token->setExpiresAt(new DateTimeImmutable('+1 hour'));
        $accessTokenRepo->persistNewAccessToken($token);

        $authenticator = new McpOAuthAuthenticator(
            $this->buildMockServerFactory($jti, $user->getId()->toRfc4122()),
            $accessTokenRepo,
            $container->get(CompanySelector::class),
            $container->get(AuthorizationCheckerInterface::class),
        );

        $request = Request::create('/_mcp', 'POST', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer fake.jwt.token']);

        // No exception: the user still belongs to the company the token was issued for.
        $passport = $authenticator->authenticate($request);
        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertSame($user->getId()->toRfc4122(), $badge->getUserIdentifier());
    }

    /**
     * Builds a mock ServerFactory whose resource server bypasses real JWT validation
     * and returns the given JTI and user ID as token attributes.
     */
    private function buildMockServerFactory(string $jti, string $userId): ServerFactoryInterface
    {
        $validatedRequest = $this->createMock(ServerRequestInterface::class);
        $validatedRequest->method('getAttribute')->willReturnMap([
            ['oauth_access_token_id', null, $jti],
            ['oauth_user_id', null, $userId],
            ['oauth_scopes', null, ['mcp:read']],
        ]);

        $resourceServer = $this->createMock(ResourceServer::class);
        $resourceServer->method('validateAuthenticatedRequest')->willReturn($validatedRequest);

        $factory = $this->createMock(ServerFactoryInterface::class);
        $factory->method('createResourceServer')->willReturn($resourceServer);

        return $factory;
    }
}
