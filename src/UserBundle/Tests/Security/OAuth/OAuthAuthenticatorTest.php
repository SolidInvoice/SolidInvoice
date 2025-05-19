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
use PHPUnit\Framework\MockObject\MockObject;
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
    private OAuthAuthenticator $authenticator;

    private ClientRegistry | MockObject $clientRegistry;

    private EntityManagerInterface | MockObject $entityManager;

    private RouterInterface | MockObject $router;

    private ToggleInterface | MockObject $toggle;

    private PropertyAccessorInterface | MockObject $propertyAccessor;

    private Security | MockObject $security;

    private UserRepository | MockObject $userRepository;

    private OAuth2ClientInterface | MockObject $client;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->toggle = $this->createMock(ToggleInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->client = $this->createMock(OAuth2ClientInterface::class);

        $this->authenticator = new OAuthAuthenticator(
            $this->clientRegistry,
            $this->entityManager,
            $this->router,
            $this->toggle,
            $this->propertyAccessor,
            $this->security
        );
    }

    public function testSupportsWithValidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', OAuthConnectCheck::ROUTE);
        $request->attributes->set('service', 'google');

        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('google_oauth_login')
            ->willReturn(true);

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsWithInvalidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'invalid_route');
        $request->attributes->set('service', 'google');

        $this->toggle
            ->expects($this->never())
            ->method('isActive');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsWithDisabledOAuth(): void
    {
        $request = new Request();
        $request->attributes->set('_route', OAuthConnectCheck::ROUTE);
        $request->attributes->set('service', 'google');

        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('google_oauth_login')
            ->willReturn(false);

        $this->assertFalse($this->authenticator->supports($request));
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

        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->client);

        $this->client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);

        $this->client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['googleId' => '123456789'])
            ->willReturn($user);

        // Execute the authenticate method
        $passport = $this->authenticator->authenticate($request);

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

        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->client);

        $this->client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);

        $this->client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        // First findOneBy returns null (no user with this OAuth ID)
        $this->userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['googleId' => '123456789']],
                [['email' => 'test@example.com']]
            )
            ->willReturnOnConsecutiveCalls(null, $user);

        // Expect property accessor to set the OAuth ID on the user
        $this->propertyAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($user, 'googleId', '123456789');

        // Expect entity manager to persist and flush the user
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Execute the authenticate method
        $passport = $this->authenticator->authenticate($request);

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

        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->client);

        $this->client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);

        $this->client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        // Both findOneBy calls return null (no existing user)
        $this->userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['googleId' => '123456789']],
                [['email' => 'test@example.com']]
            )
            ->willReturnOnConsecutiveCalls(null, null);

        // No current user
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // Registration is allowed
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(true);

        // Expect property accessor to set the OAuth ID on the new user
        $this->propertyAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with(
                $this->callback(function ($user) {
                    return $user instanceof User && $user->getEmail() === 'test@example.com';
                }),
                'googleId',
                '123456789'
            );

        // Expect entity manager to persist and flush the user
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) {
                return $user instanceof User && $user->getEmail() === 'test@example.com';
            }));
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Execute the authenticate method
        $passport = $this->authenticator->authenticate($request);

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

        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->client);

        $this->client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);

        $this->client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        // Both findOneBy calls return null (no existing user)
        $this->userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['googleId' => '123456789']],
                [['email' => 'test@example.com']]
            )
            ->willReturnOnConsecutiveCalls(null, null);

        // No current user
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // Registration is not allowed
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(false);

        // Execute the authenticate method
        $passport = $this->authenticator->authenticate($request);

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

        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->client);

        $this->client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);

        $this->client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        // Both findOneBy calls return null (no existing user)
        $this->userRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive(
                [['googleId' => '123456789']],
                [['email' => 'test@example.com']]
            )
            ->willReturnOnConsecutiveCalls(null, null);

        // Return current user
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        // Expect property accessor to set the OAuth ID on the current user
        $this->propertyAccessor
            ->expects($this->once())
            ->method('setValue')
            ->with($currentUser, 'googleId', '123456789');

        // Expect entity manager to persist and flush the user
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($currentUser);
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Execute the authenticate method
        $passport = $this->authenticator->authenticate($request);

        // Extract and execute the user loader
        $userBadge = $passport->getBadge(UserBadge::class);
        $result = $userBadge->getUser();

        $this->assertSame($currentUser, $result);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('_select_company')
            ->willReturn('/select-company');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

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

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('_login')
            ->willReturn('/login');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }

    public function testStart(): void
    {
        $request = new Request();

        $this->router->expects($this->once())
            ->method('generate')
            ->with('_login')
            ->willReturn('/login');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }
}
