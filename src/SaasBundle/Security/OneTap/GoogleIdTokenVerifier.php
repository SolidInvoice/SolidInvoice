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

namespace SolidInvoice\SaasBundle\Security\OneTap;

use Google\Client;
use SolidInvoice\UserBundle\OAuth\GoogleIdentity;
use Throwable;
use function is_array;
use function is_string;

/**
 * Verifies Google One Tap ID tokens using the official Google API PHP client.
 *
 * {@see Client::verifyIdToken()} validates the JWT signature against Google's
 * published keys and checks the issuer, audience (our client id) and expiry. On
 * top of that we require the email address to be verified before trusting the
 * identity.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Security\OneTap\GoogleIdTokenVerifierTest
 */
final readonly class GoogleIdTokenVerifier implements IdTokenVerifierInterface
{
    public function __construct(
        private Client $client,
    ) {
    }

    public function verify(string $idToken): OneTapToken
    {
        try {
            $payload = $this->client->verifyIdToken($idToken);
        } catch (Throwable $e) {
            throw new InvalidIdTokenException('Unable to verify the Google ID token.', 0, $e);
        }

        if (! is_array($payload)) {
            throw new InvalidIdTokenException('The Google ID token is invalid.');
        }

        if (($payload['email_verified'] ?? false) !== true) {
            throw new InvalidIdTokenException('The Google account email address is not verified.');
        }

        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;

        if (! is_string($googleId) || $googleId === '' || ! is_string($email) || $email === '') {
            throw new InvalidIdTokenException('The Google ID token is missing required claims.');
        }

        $identity = new GoogleIdentity(
            googleId: $googleId,
            email: $email,
            emailVerified: true,
            firstName: is_string($payload['given_name'] ?? null) ? $payload['given_name'] : null,
            lastName: is_string($payload['family_name'] ?? null) ? $payload['family_name'] : null,
        );

        $nonce = $payload['nonce'] ?? null;

        return new OneTapToken($identity, is_string($nonce) ? $nonce : null);
    }
}
