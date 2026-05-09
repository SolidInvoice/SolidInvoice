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

namespace SolidInvoice\UserBundle\Tests\Security\OAuth;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Action\Security\OAuthConnectCheck;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Security\OAuth\OAuthAuthenticator;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/** @covers \SolidInvoice\UserBundle\Security\OAuth\OAuthAuthenticator */
final class OAuthAuthenticatorTest extends TestCase
{
    private function createAuthenticator(
        ?ClientRegistry $clientRegistry = null,
        ?EntityManagerInterface $entityManager = null,
        ?RouterInterface $router = null,
        ?ToggleInterface $toggle = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?Security $security = null,
    ): OAuthAuthenticator {
        return new OAuthAuthenticator(
            $clientRegistry ?? $this->createStub(ClientRegistry::class),
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $router ?? $this->createStub(RouterInterface::class),
            $toggle ?? $this->createStub(ToggleInterface::class),
            $propertyAccessor ?? $this->createStub(PropertyAccessorInterface::class),
            $security ?? $this->createStub(Security::class),
        );
    }

    public function testSupportsWithValidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', OAuthConnectCheck::ROUTE);
        $request->attributes->set('service', 'google');

        $toggle = $this->createMock(ToggleInterface::class);
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('google_oauth_login')
            ->willReturn(true);

        $authenticator = $this->createAuthenticator(toggle: $toggle);

        $this->assertTrue($authenticator->supports($request));
    }

    public function testSupportsWithInvalidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'invalid_route');
        $request->attributes->set('service', 'google');

        $toggle = $this->createMock(ToggleInterface::class);
        $toggle
            ->expects($this->never())
            ->method('isActive');

        $authenticator = $this->createAuthenticator(toggle: $toggle);

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsWithDisabledOAuth(): void
    {
        $request = new Request();
        $request->attributes->set('_route', OAuthConnectCheck::ROUTE);
        $request->attributes->set('service', 'google');

        $toggle = $this->createMock(ToggleInterface::class);
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('google_oauth_login')
            ->willReturn(false);

        $authenticator = $this->createAuthenticator(toggle: $toggle);

        $this->assertFalse($authenticator->supports($request));
    }

    public function testAuthenticateWithExistingUser(): void
    {
        $request = new Request();
        $request->attributes->set('service', 'google');

        $accessToken = new AccessToken(['access_token' => 'test_token']);
        $user = new User();

        $googleUser = new GoogleUser([
            'sub' => '123456789',
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);
        $client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['googleId' => '123456789'])
            ->willReturn($user);

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $authenticator = $this->createAuthenticator(
            clientRegistry: $clientRegistry,
            entityManager: $entityManager,
        );

        // Execute the authenticate method
        $passport = $authenticator->authenticate($request);

        // Extract and execute the user loader
        $userBadge = $passport->getBadge(UserBadge::class);

        $result = $userBadge->getUser();

        $this->assertSame($user, $result);
    }

    public function testAuthenticateWithExistingEmailButNoOAuthId(): void
    {
        $request = new Request();
        $request->attributes->set('service', 'google');

        $accessToken = new AccessToken(['access_token' => 'test_token']);
        $user = new User();

        $googleUser = new GoogleUser([
            'sub' => '123456789',
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);
        $client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $userRepository = $this->createMock(UserRepository::class);
        // First findOneBy returns null (no user with this OAuth ID), second returns user
        $userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($user) {
                if (isset($criteria['googleId']) && $criteria['googleId'] === '123456789') {
                    return null;
                }
                if (isset($criteria['email']) && $criteria['email'] === 'test@example.com') {
                    return $user;
                }
                return null;
            });

        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        // Expect property accessor to set the OAuth ID on the user
        $propertyAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($user, 'googleId', '123456789');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        // Expect entity manager to persist and flush the user
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client);

        $authenticator = $this->createAuthenticator(
            clientRegistry: $clientRegistry,
            entityManager: $entityManager,
            propertyAccessor: $propertyAccessor,
        );

        // Execute the authenticate method
        $passport = $authenticator->authenticate($request);

        // Extract and execute the user loader
        $userBadge = $passport->getBadge(UserBadge::class);
        $result = $userBadge->getUser();

        $this->assertSame($user, $result);
    }

    public function testAuthenticateWithNewUserAndRegistrationAllowed(): void
    {
        $request = new Request();
        $request->attributes->set('service', 'google');

        $accessToken = new AccessToken(['access_token' => 'test_token']);

        $googleUser = new GoogleUser([
            'sub' => '123456789',
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);
        $client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $userRepository = $this->createMock(UserRepository::class);
        // Both findOneBy calls return null (no existing user)
        $userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if ((isset($criteria['googleId']) && $criteria['googleId'] === '123456789') ||
                    (isset($criteria['email']) && $criteria['email'] === 'test@example.com')) {
                    return null;
                }
                return null;
            });

        $security = $this->createMock(Security::class);
        // No current user
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $toggle = $this->createMock(ToggleInterface::class);
        // Registration is allowed
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(true);

        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        // Expect property accessor to set the OAuth ID on the new user
        $propertyAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with(
                $this->callback(function ($user) {
                    return $user instanceof User && $user->getEmail() === 'test@example.com';
                }),
                'googleId',
                '123456789'
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        // Expect entity manager to persist and flush the user
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) {
                return $user instanceof User && $user->getEmail() === 'test@example.com';
            }));
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client);

        $authenticator = $this->createAuthenticator(
            clientRegistry: $clientRegistry,
            entityManager: $entityManager,
            toggle: $toggle,
            propertyAccessor: $propertyAccessor,
            security: $security,
        );

        // Execute the authenticate method
        $passport = $authenticator->authenticate($request);

        // Extract and execute the user loader
        $userBadge = $passport->getBadge(UserBadge::class);
        $result = $userBadge->getUser();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->getEmail());
        $this->assertTrue($result->isEnabled());
        $this->assertTrue($result->isVerified());
    }

    public function testAuthenticateWithNewUserAndRegistrationNotAllowed(): void
    {
        $request = new Request();
        $request->attributes->set('service', 'google');

        $accessToken = new AccessToken(['access_token' => 'test_token']);

        $googleUser = new GoogleUser([
            'sub' => '123456789',
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);
        $client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $userRepository = $this->createMock(UserRepository::class);
        // Both findOneBy calls return null (no existing user)
        $userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if ((isset($criteria['googleId']) && $criteria['googleId'] === '123456789') ||
                    (isset($criteria['email']) && $criteria['email'] === 'test@example.com')) {
                    return null;
                }
                return null;
            });

        $security = $this->createMock(Security::class);
        // No current user
        $security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $toggle = $this->createMock(ToggleInterface::class);
        // Registration is not allowed
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client);

        $authenticator = $this->createAuthenticator(
            clientRegistry: $clientRegistry,
            entityManager: $entityManager,
            toggle: $toggle,
            security: $security,
        );

        // Execute the authenticate method
        $passport = $authenticator->authenticate($request);

        // Extract and execute the user loader
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->expectException(UserNotFoundException::class);
        $userBadge->getUser();
    }

    public function testAuthenticateWithCurrentUser(): void
    {
        $request = new Request();
        $request->attributes->set('service', 'google');

        $accessToken = new AccessToken(['access_token' => 'test_token']);
        $currentUser = new User();
        $currentUser->setEmail('current@example.com');

        $googleUser = new GoogleUser([
            'sub' => '123456789',
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);
        $client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $userRepository = $this->createMock(UserRepository::class);
        // Both findOneBy calls return null (no existing user)
        $userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if ((isset($criteria['googleId']) && $criteria['googleId'] === '123456789') ||
                    (isset($criteria['email']) && $criteria['email'] === 'test@example.com')) {
                    return null;
                }
                return null;
            });

        $security = $this->createMock(Security::class);
        // Return current user
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        // Expect property accessor to set the OAuth ID on the current user
        $propertyAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($currentUser, 'googleId', '123456789');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);
        // Expect entity manager to persist and flush the user
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($currentUser);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client);

        $authenticator = $this->createAuthenticator(
            clientRegistry: $clientRegistry,
            entityManager: $entityManager,
            propertyAccessor: $propertyAccessor,
            security: $security,
        );

        // Execute the authenticate method
        $passport = $authenticator->authenticate($request);

        // Extract and execute the user loader
        $userBadge = $passport->getBadge(UserBadge::class);
        $result = $userBadge->getUser();

        $this->assertSame($currentUser, $result);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createStub(TokenInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('_select_company')
            ->willReturn('/select-company');

        $authenticator = $this->createAuthenticator(router: $router);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/select-company', $response->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $flashBag = $this->createMock(FlashBagInterface::class);
        $request = new Request();
        $session = new Session(storage: new MockArraySessionStorage(), flashes: $flashBag);
        $request->setSession($session);
        $exception = new AuthenticationException('Authentication failed');

        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('error', 'An authentication exception occurred.');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('_login_main')
            ->willReturn('/login');

        $authenticator = $this->createAuthenticator(router: $router);

        $response = $authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }

    public function testStart(): void
    {
        $request = new Request();

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('_login_main')
            ->willReturn('/login');

        $authenticator = $this->createAuthenticator(router: $router);

        $response = $authenticator->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }
}
