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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Action\Security\OAuthConnectCheck;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\OAuth\GoogleUserProvisioner;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Security\OAuth\OAuthAuthenticator;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

#[CoversClass(OAuthAuthenticator::class)]
final class OAuthAuthenticatorTest extends TestCase
{
    // Collaborators default to stubs; a test that asserts on calls swaps in a
    // mock before calling authenticator().
    private ClientRegistry & Stub $clientRegistry;

    private EntityManagerInterface & Stub $entityManager;

    private RouterInterface & Stub $router;

    private ToggleInterface & Stub $toggle;

    private Security & Stub $security;

    private UserRepository & Stub $userRepository;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createStub(ClientRegistry::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->router = $this->createStub(RouterInterface::class);
        $this->toggle = $this->createStub(ToggleInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->userRepository = $this->createStub(UserRepository::class);
    }

    public function testSupportsWithValidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', OAuthConnectCheck::ROUTE);
        $request->attributes->set('service', 'google');

        $toggle = $this->createMock(ToggleInterface::class);
        $this->toggle = $toggle;
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('google_oauth_login')
            ->willReturn(true);

        self::assertTrue($this->authenticator()->supports($request));
    }

    public function testSupportsWithInvalidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'invalid_route');
        $request->attributes->set('service', 'google');

        $toggle = $this->createMock(ToggleInterface::class);
        $this->toggle = $toggle;
        $toggle->expects($this->never())->method('isActive');

        self::assertFalse($this->authenticator()->supports($request));
    }

    public function testSupportsWithDisabledOAuth(): void
    {
        $request = new Request();
        $request->attributes->set('_route', OAuthConnectCheck::ROUTE);
        $request->attributes->set('service', 'google');

        $toggle = $this->createMock(ToggleInterface::class);
        $this->toggle = $toggle;
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('google_oauth_login')
            ->willReturn(false);

        self::assertFalse($this->authenticator()->supports($request));
    }

    public function testAuthenticateWithExistingUser(): void
    {
        $user = new User();
        $googleUser = $this->googleUser();

        $this->prepareClient($googleUser);

        $this->userRepository
            ->method('findOneBy')
            ->willReturnCallback(static fn (array $criteria): ?User => isset($criteria['googleId']) ? $user : null);

        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->never())->method('flush');

        $result = $this->resolveUser($this->authenticator()->authenticate($this->request()));

        self::assertSame($user, $result);
    }

    public function testAuthenticateWithExistingEmailButNoOAuthId(): void
    {
        $user = new User();
        $googleUser = $this->googleUser();

        $this->prepareClient($googleUser);

        $this->userRepository
            ->method('findOneBy')
            ->willReturnCallback(static fn (array $criteria): ?User => isset($criteria['email']) ? $user : null);

        $this->security->method('getUser')->willReturn(null);
        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $result = $this->resolveUser($this->authenticator()->authenticate($this->request()));

        self::assertSame($user, $result);
        self::assertSame('123456789', $user->getGoogleId());
    }

    public function testAuthenticateWithNewUserAndRegistrationAllowed(): void
    {
        $this->prepareClient($this->googleUser());

        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->security->method('getUser')->willReturn(null);
        $toggle = $this->createMock(ToggleInterface::class);
        $this->toggle = $toggle;
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(true);

        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $result = $this->resolveUser($this->authenticator()->authenticate($this->request()));

        self::assertSame('test@example.com', $result->getEmail());
        self::assertSame('123456789', $result->getGoogleId());
        self::assertTrue($result->isEnabled());
        self::assertTrue($result->isVerified());
    }

    public function testAuthenticateWithNewUserAndRegistrationNotAllowed(): void
    {
        $this->prepareClient($this->googleUser());

        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->security->method('getUser')->willReturn(null);
        $toggle = $this->createMock(ToggleInterface::class);
        $this->toggle = $toggle;
        $toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(false);

        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->never())->method('flush');

        $userBadge = $this->authenticator()->authenticate($this->request())->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $userBadge);

        $this->expectException(UserNotFoundException::class);
        $userBadge->getUser();
    }

    public function testAuthenticateWithCurrentUser(): void
    {
        $currentUser = new User();
        $currentUser->setEmail('current@example.com');

        $this->prepareClient($this->googleUser());

        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->security->method('getUser')->willReturn($currentUser);

        $entityManager = $this->mockEntityManager();
        $entityManager->expects($this->once())->method('persist')->with($currentUser);
        $entityManager->expects($this->once())->method('flush');

        $result = $this->resolveUser($this->authenticator()->authenticate($this->request()));

        self::assertSame($currentUser, $result);
        self::assertSame('123456789', $currentUser->getGoogleId());
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createStub(TokenInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $this->router = $router;
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('_select_company')
            ->willReturn('/select-company');

        $response = $this->authenticator()->onAuthenticationSuccess($request, $token, 'main');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/select-company', $response->getTargetUrl());
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
        $this->router = $router;
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('_login_main')
            ->willReturn('/login');

        $response = $this->authenticator()->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
    }

    public function testStart(): void
    {
        $request = new Request();

        $router = $this->createMock(RouterInterface::class);
        $this->router = $router;
        $router->expects($this->once())
            ->method('generate')
            ->with('_login_main')
            ->willReturn('/login');

        $response = $this->authenticator()->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
    }

    private function authenticator(): OAuthAuthenticator
    {
        return new OAuthAuthenticator(
            $this->clientRegistry,
            $this->router,
            $this->toggle,
            $this->security,
            new GoogleUserProvisioner($this->entityManager, $this->toggle),
        );
    }

    private function mockEntityManager(): EntityManagerInterface & MockObject
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
        $entityManager->method('getRepository')->willReturn($this->userRepository);

        return $entityManager;
    }

    private function request(): Request
    {
        $request = new Request();
        $request->attributes->set('service', 'google');

        return $request;
    }

    private function googleUser(): GoogleUser
    {
        return new GoogleUser([
            'sub' => '123456789',
            'email' => 'test@example.com',
            'email_verified' => true,
        ]);
    }

    private function prepareClient(GoogleUser $googleUser): void
    {
        $accessToken = new AccessToken(['access_token' => 'test_token']);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $clientRegistry = $this->createMock(ClientRegistry::class);
        $this->clientRegistry = $clientRegistry;

        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client);

        $client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $client
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($googleUser);
    }

    private function resolveUser(Passport $passport): User
    {
        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $userBadge);

        $user = $userBadge->getUser();
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
