<?php

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

final readonly class OAuthUser
{
    public function __construct(
        private ResourceOwnerInterface $resourceOwner
    ) {
    }

    public function getEmail(): ?string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->getEmail(),
            default => null,
        };
    }

    public function getFirstName(): string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->getFirstName(),
            default => '',
        };
    }

    public function getId(): string
    {
        return $this->resourceOwner->getId();
    }

    public function getLastName(): string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->getLastName(),
            default => '',
        };
    }

    public function getPropertyMap(): string
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => 'googleId',
            default => '',
        };
    }

    public function getEmailVerified(): bool
    {
        return match (true) {
            $this->resourceOwner instanceof GoogleUser => $this->resourceOwner->toArray()['email_verified'] ?? false,
            default => false,
        };
    }
}
