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

namespace SolidInvoice\SaasBundle\Tests\Action\OneTap;

use const JSON_THROW_ON_ERROR;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Action\OneTap\VerifyOneTapAction;
use SolidInvoice\SaasBundle\Security\OneTap\IdTokenVerifierInterface;
use SolidInvoice\SaasBundle\Security\OneTap\InvalidIdTokenException;
use SolidInvoice\SaasBundle\Security\OneTap\NonceStore;
use SolidInvoice\SaasBundle\Security\OneTap\OneTapToken;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\OAuth\GoogleIdentity;
use SolidInvoice\UserBundle\OAuth\GoogleUserProvisionerInterface;
use SolidInvoice\UserBundle\OAuth\ProvisionedUser;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use function json_decode;

#[CoversClass(VerifyOneTapAction::class)]
final class VerifyOneTapActionTest extends TestCase
{
    private NonceStore $nonceStore;

    protected function setUp(): void
    {
        $this->nonceStore = new NonceStore(new ArrayAdapter(), 300);
    }

    public function testReturnsALoginLinkForAValidCredential(): void
    {
        $nonce = $this->nonceStore->create();
        $user = new User()->setEmail('user@example.com');

        $action = $this->action(
            verifier: $this->verifierReturning(new OneTapToken($this->identity(), $nonce)),
            provisioner: $this->provisionerReturning(new ProvisionedUser($user, true)),
            loginLinkHandler: $this->loginLinkHandler('https://app.test/onetap/login-check?token=abc'),
        );

        $response = $action($this->request('jwt'));

        self::assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('https://app.test/onetap/login-check?token=abc', $payload['loginLink']);
        self::assertTrue($payload['newUser']);

        // The nonce must be single-use: a replay with the same nonce is rejected.
        self::assertFalse($this->nonceStore->consume($nonce));
    }

    public function testReturnsBadRequestWhenCredentialMissing(): void
    {
        $action = $this->action();

        $response = $action(Request::create('/onetap/verify', Request::METHOD_POST, content: '{}'));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testReturnsUnauthorizedForAnInvalidToken(): void
    {
        $verifier = $this->createMock(IdTokenVerifierInterface::class);
        $verifier->method('verify')->willThrowException(new InvalidIdTokenException('bad'));

        $response = $this->action(verifier: $verifier)($this->request('jwt'));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testReturnsForbiddenForAnInvalidOrReusedNonce(): void
    {
        $action = $this->action(
            verifier: $this->verifierReturning(new OneTapToken($this->identity(), 'never-issued')),
        );

        $response = $action($this->request('jwt'));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testReturnsForbiddenWhenRegistrationIsDisabled(): void
    {
        $nonce = $this->nonceStore->create();

        $action = $this->action(
            verifier: $this->verifierReturning(new OneTapToken($this->identity(), $nonce)),
            provisioner: $this->provisionerReturning(null),
        );

        $response = $action($this->request('jwt'));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testReturnsNotFoundWhenFeatureDisabled(): void
    {
        $action = $this->action(toggleActive: false);

        $this->expectException(NotFoundHttpException::class);

        $action($this->request('jwt'));
    }

    private function action(
        bool $toggleActive = true,
        ?IdTokenVerifierInterface $verifier = null,
        ?GoogleUserProvisionerInterface $provisioner = null,
        ?LoginLinkHandlerInterface $loginLinkHandler = null,
    ): VerifyOneTapAction {
        $toggle = $this->createMock(ToggleInterface::class);
        $toggle->method('isActive')->willReturn($toggleActive);

        return new VerifyOneTapAction(
            $toggle,
            $verifier ?? $this->createStub(IdTokenVerifierInterface::class),
            $this->nonceStore,
            $provisioner ?? $this->createStub(GoogleUserProvisionerInterface::class),
            $loginLinkHandler ?? $this->loginLinkHandler('https://app.test/onetap/login-check'),
        );
    }

    private function identity(): GoogleIdentity
    {
        return new GoogleIdentity('gid-1', 'user@example.com', true, 'Ada', 'Lovelace');
    }

    private function verifierReturning(OneTapToken $token): IdTokenVerifierInterface
    {
        $verifier = $this->createMock(IdTokenVerifierInterface::class);
        $verifier->method('verify')->willReturn($token);

        return $verifier;
    }

    private function provisionerReturning(?ProvisionedUser $result): GoogleUserProvisionerInterface
    {
        $provisioner = $this->createMock(GoogleUserProvisionerInterface::class);
        $provisioner->method('findOrCreate')->willReturn($result);

        return $provisioner;
    }

    private function loginLinkHandler(string $url): LoginLinkHandlerInterface
    {
        $handler = $this->createMock(LoginLinkHandlerInterface::class);
        $handler->method('createLoginLink')->willReturn(new LoginLinkDetails($url, CarbonImmutable::now()->addSeconds(60)));

        return $handler;
    }

    private function request(string $credential): Request
    {
        return Request::create('/onetap/verify', Request::METHOD_POST, content: (string) json_encode(['credential' => $credential]));
    }
}
