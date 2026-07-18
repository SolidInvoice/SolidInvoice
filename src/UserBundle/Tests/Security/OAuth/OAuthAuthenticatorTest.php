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
    private OAuthAuthenticator $authenticator;

    private ClientRegistry & MockObject $clientRegistry;

    private EntityManagerInterface & MockObject $entityManager;

    private RouterInterface & MockObject $router;

    private ToggleInterface & MockObject $toggle;

    private Security & MockObject $security;

    private UserRepository & MockObject $userRepository;

    private OAuth2ClientInterface & MockObject $client;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->toggle = $this->createMock(ToggleInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->client = $this->createMock(OAuth2ClientInterface::class);

        $this->entityManager
            ->method('getRepository')
            ->willReturn($this->userRepository);

        $this->authenticator = new OAuthAuthenticator(
            $this->clientRegistry,
            $this->router,
            $this->toggle,
            $this->security,
            new GoogleUserProvisioner($this->entityManager, $this->toggle),
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

        self::assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsWithInvalidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'invalid_route');
        $request->attributes->set('service', 'google');

        $this->toggle->expects($this->never())->method('isActive');

        self::assertFalse($this->authenticator->supports($request));
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

        self::assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithExistingUser(): void
    {
        $user = new User();
        $googleUser = $this->googleUser();

        $this->prepareClient($googleUser);

        $this->userRepository
            ->method('findOneBy')
            ->willReturnCallback(static fn (array $criteria): ?User => isset($criteria['googleId']) ? $user : null);

        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->resolveUser($this->authenticator->authenticate($this->request()));

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
        $this->entityManager->expects($this->once())->method('persist')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->resolveUser($this->authenticator->authenticate($this->request()));

        self::assertSame($user, $result);
        self::assertSame('123456789', $user->getGoogleId());
    }

    public function testAuthenticateWithNewUserAndRegistrationAllowed(): void
    {
        $this->prepareClient($this->googleUser());

        $this->userRepository->method('findOneBy')->willReturn(null);
        $this->security->method('getUser')->willReturn(null);
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(true);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->resolveUser($this->authenticator->authenticate($this->request()));

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
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('allow_registration')
            ->willReturn(false);

        $this->entityManager->expects($this->never())->method('flush');

        $userBadge = $this->authenticator->authenticate($this->request())->getBadge(UserBadge::class);
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

        $this->entityManager->expects($this->once())->method('persist')->with($currentUser);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->resolveUser($this->authenticator->authenticate($this->request()));

        self::assertSame($currentUser, $result);
        self::assertSame('123456789', $currentUser->getGoogleId());
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createStub(TokenInterface::class);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('_select_company')
            ->willReturn('/select-company');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

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

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('_login_main')
            ->willReturn('/login');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
    }

    public function testStart(): void
    {
        $request = new Request();

        $this->router->expects($this->once())
            ->method('generate')
            ->with('_login_main')
            ->willReturn('/login');

        $response = $this->authenticator->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/login', $response->getTargetUrl());
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

        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($this->client);

        $this->client
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->client
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
