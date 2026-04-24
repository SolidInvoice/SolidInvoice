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

namespace SolidInvoice\McpBundle\Action;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use SolidInvoice\McpBundle\OAuth\KeyManager;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\McpRefreshTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/oauth/revoke', name: 'mcp_oauth_revoke', methods: ['POST'])]
final class Revoke
{
    public function __construct(
        private readonly McpAccessTokenRepository $accessTokenRepository,
        private readonly McpRefreshTokenRepository $refreshTokenRepository,
        private readonly KeyManager $keyManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $token = (string) $request->request->get('token', '');
        $tokenTypeHint = (string) $request->request->get('token_type_hint', '');

        if ($token === '') {
            return new JsonResponse(['error' => 'invalid_request'], Response::HTTP_BAD_REQUEST);
        }

        if ($tokenTypeHint !== 'refresh_token' && $this->tryRevokeAccessToken($token)) {
            return new Response('', Response::HTTP_OK);
        }

        $this->refreshTokenRepository->revokeRefreshToken($token);

        // RFC 7009 prescribes 200 OK for unknown tokens.
        return new Response('', Response::HTTP_OK);
    }

    private function tryRevokeAccessToken(string $token): bool
    {
        try {
            $signer = new Sha256();
            $verificationKey = InMemory::file($this->keyManager->getPublicKeyPath());

            $config = Configuration::forAsymmetricSigner(
                $signer,
                InMemory::plainText('empty', 'empty'),
                $verificationKey,
            );

            $parsed = $config->parser()->parse($token);

            if (! $parsed instanceof UnencryptedToken) {
                return false;
            }

            // Reject forged or tampered tokens before trusting any claim — the
            // revocation endpoint is unauthenticated (RFC 7009), so verifying
            // the RS256 signature is the only thing binding the request to a
            // legitimate token.
            $config->validator()->assert($parsed, new SignedWith($signer, $verificationKey));

            $jti = $parsed->claims()->get('jti');

            if (! \is_string($jti) || $jti === '') {
                return false;
            }

            $this->accessTokenRepository->revokeAccessToken($jti);

            return true;
        } catch (RequiredConstraintsViolated | \InvalidArgumentException | \RuntimeException) {
            return false;
        }
    }
}
