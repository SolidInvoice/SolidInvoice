<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Tests\Security;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Generator\VerifyEmailTokenGenerator;
use SymfonyCasts\Bundle\VerifyEmail\Util\VerifyEmailQueryUtility;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;

final class EmailVerifierTest extends TestCase
{
    private EmailVerifier $emailVerifier;

    private VerifyEmailHelper $verifyEmailHelper;

    private MailerInterface|MockObject $mailer;

    private UserRepository|MockObject $userRepository;

    private UrlGeneratorInterface|MockObject $urlGenerator;

    protected function setUp(): void
    {
        // Create mocks for dependencies
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        // Create real instances for VerifyEmailHelper dependencies
        $uriSigner = new UriSigner('test_signing_key');
        $queryUtility = new VerifyEmailQueryUtility();
        $tokenGenerator = new VerifyEmailTokenGenerator('test_signing_key');
        $lifetime = 3600; // 1 hour

        $this->verifyEmailHelper = new VerifyEmailHelper(
            $this->urlGenerator,
            $uriSigner,
            $queryUtility,
            $tokenGenerator,
            $lifetime
        );

        $this->emailVerifier = new EmailVerifier(
            $this->verifyEmailHelper,
            $this->mailer,
            $this->userRepository
        );
    }

    public function testSendEmailConfirmation(): void
    {
        $user = $this->createUser();
        $email = $this->createMock(TemplatedEmail::class);
        $routeName = 'app_verify_email';

        // Set up the URL generator to return a valid URL
        $generatedUrl = 'https://example.com/verify-email';
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $routeName,
                $this->callback(function ($params) use ($user) {
                    // Verify that the parameters contain token, expires, and id
                    return isset($params['token'], $params['expires']) && $params['id'] === $user->getId()?->toBase58();
                }),
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($generatedUrl);

        $email->expects($this->once())
            ->method('getContext')
            ->willReturn([]);

        $email->expects($this->once())
            ->method('context')
            ->with($this->callback(function ($context) {
                // Verify that the context contains the required keys
                return isset($context['signedUrl'], $context['expiresAtMessageKey'], $context['expiresAtMessageData']);
            }))
            ->willReturnSelf();

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($email);

        $this->emailVerifier->sendEmailConfirmation($routeName, $user, $email);
    }

    public function testHandleEmailConfirmation(): void
    {
        $user = $this->createUser();

        // Create a request with the necessary parameters
        $request = new Request();

        // Create a token using the same algorithm as VerifyEmailTokenGenerator
        $tokenGenerator = new VerifyEmailTokenGenerator('test_signing_key');
        $token = $tokenGenerator->createToken($user->getId(), $user->getEmail());

        // Set the necessary query parameters
        $request->query->set('token', $token);
        $request->query->set('expires', time() + 3600); // Future expiration

        // Mock the UriSigner's checkRequest method to return true via reflection
        $uriSignerReflection = new \ReflectionProperty($this->verifyEmailHelper, 'uriSigner');
        $uriSigner = $uriSignerReflection->getValue($this->verifyEmailHelper);

        $uriSignerMock = $this->createMock(UriSigner::class);
        $uriSignerMock->method('checkRequest')->willReturn(true);
        $uriSignerReflection->setValue($this->verifyEmailHelper, $uriSignerMock);

        // Expect the repository to save the user
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->emailVerifier->handleEmailConfirmation($request, $user);

        // Restore the original UriSigner
        $uriSignerReflection->setValue($this->verifyEmailHelper, $uriSigner);

        $this->assertTrue($user->isVerified());
    }

    public function testHandleEmailConfirmationWithException(): void
    {
        $user = $this->createUser();
        $request = new Request();

        // Set up the request with invalid query parameters to trigger an exception
        // Using an expired timestamp will cause an ExpiredSignatureException
        $request->query->set('token', 'invalid_token');
        $request->query->set('expires', time() - 3600); // Expired timestamp

        // Mock the UriSigner's checkRequest method to return true via reflection
        // so we get past the signature check and hit the expiration check
        $uriSignerReflection = new \ReflectionProperty($this->verifyEmailHelper, 'uriSigner');
        $uriSigner = $uriSignerReflection->getValue($this->verifyEmailHelper);

        $uriSignerMock = $this->createMock(UriSigner::class);
        $uriSignerMock->method('checkRequest')->willReturn(true);
        $uriSignerReflection->setValue($this->verifyEmailHelper, $uriSignerMock);

        // Expect the repository to never save the user
        $this->userRepository->expects($this->never())
            ->method('save');

        $this->expectException(VerifyEmailExceptionInterface::class);

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } finally {
            $this->assertFalse($user->isVerified());
            // Restore the original UriSigner
            $uriSignerReflection->setValue($this->verifyEmailHelper, $uriSigner);
        }
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');

        return $user;
    }
}
