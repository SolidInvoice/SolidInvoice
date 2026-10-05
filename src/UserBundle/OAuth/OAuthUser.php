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

namespace SolidInvoice\UserBundle\OAuth;

use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

use function is_scalar;

/**
 * @see \SolidInvoice\UserBundle\Tests\OAuth\OAuthUserTest
 */
final readonly class OAuthUser
{
    public function __construct(
        private ResourceOwnerInterface $resourceOwner,
        private string $service = 'google',
    ) {
    }

    public function getEmail(): ?string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->getEmail(),
            default => $this->getClaim('email'),
        };
    }

    public function getFirstName(): string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->getFirstName(),
            default => $this->getClaim('given_name') ?? '',
        };
    }

    public function getId(): string
    {
        return (string) $this->resourceOwner->getId();
    }

    public function getLastName(): string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->getLastName(),
            default => $this->getClaim('family_name') ?? '',
        };
    }

    /**
     * Name of the property on the User entity that stores the provider identifier.
     */
    public function getPropertyMap(): string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => 'googleId',
            $this->service === 'oidc' => 'oidcId',
            default => '',
        };
    }

    public function getEmailVerified(): bool
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->toArray()['email_verified'] ?? false,
            default => (bool) ($this->getClaim('email_verified') ?? false),
        };
    }

    /**
     * Reads a claim from a non-Google (OIDC) resource owner, which exposes the
     * raw userinfo response as an array.
     */
    private function getClaim(string $claim): ?string
    {
        $value = $this->resourceOwner->toArray()[$claim] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
