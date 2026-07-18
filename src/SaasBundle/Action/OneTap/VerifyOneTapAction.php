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

namespace SolidInvoice\SaasBundle\Action\OneTap;

use const JSON_THROW_ON_ERROR;
use JsonException;
use SolidInvoice\SaasBundle\Security\OneTap\IdTokenVerifierInterface;
use SolidInvoice\SaasBundle\Security\OneTap\InvalidIdTokenException;
use SolidInvoice\SaasBundle\Security\OneTap\NonceStore;
use SolidInvoice\UserBundle\OAuth\GoogleUserProvisionerInterface;
use SolidInvoice\UserBundle\OAuth\ProvisionedUser;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use function is_array;
use function is_string;
use function json_decode;

/**
 * Verifies a Google One Tap ID token posted by the marketing site, resolves (or
 * creates) the matching user, and returns a single-use login link the widget
 * redirects the browser to.
 *
 * The endpoint is stateless: it authenticates purely from the signed JWT plus
 * the single-use nonce, never from a session cookie.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Action\OneTap\VerifyOneTapActionTest
 */
final readonly class VerifyOneTapAction
{
    use OneTapRateLimit;

    public function __construct(
        private ToggleInterface $toggle,
        private IdTokenVerifierInterface $verifier,
        private NonceStore $nonceStore,
        private GoogleUserProvisionerInterface $provisioner,
        #[Autowire(service: 'security.authenticator.login_link_handler.main')]
        private LoginLinkHandlerInterface $loginLinkHandler,
        #[Autowire(service: 'limiter.one_tap_verify')]
        private ?RateLimiterFactory $limiter = null,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->toggle->isActive('google_one_tap')) {
            throw new NotFoundHttpException();
        }

        if (($limited = $this->enforceRateLimit($this->limiter, $request)) instanceof JsonResponse) {
            return $limited;
        }

        $credential = $this->extractCredential($request);

        if ($credential === null) {
            return new JsonResponse(['error' => 'Missing credential.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = $this->verifier->verify($credential);
        } catch (InvalidIdTokenException) {
            return new JsonResponse(['error' => 'Invalid credential.'], Response::HTTP_UNAUTHORIZED);
        }

        // A missing nonce claim collapses to '', which is never a stored nonce,
        // so tokens without a nonce are rejected here too.
        if (! $this->nonceStore->consume((string) $token->nonce)) {
            return new JsonResponse(['error' => 'Invalid or expired nonce.'], Response::HTTP_FORBIDDEN);
        }

        $provisioned = $this->provisioner->findOrCreate($token->identity);

        if (! $provisioned instanceof ProvisionedUser) {
            return new JsonResponse(['error' => 'Registration is not allowed.'], Response::HTTP_FORBIDDEN);
        }

        $loginLink = $this->loginLinkHandler->createLoginLink($provisioned->user, $request);

        return new JsonResponse([
            'loginLink' => $loginLink->getUrl(),
            'newUser' => $provisioned->isNew,
        ]);
    }

    private function extractCredential(Request $request): ?string
    {
        try {
            $data = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        $credential = $data['credential'] ?? null;

        return is_string($credential) && $credential !== '' ? $credential : null;
    }
}
