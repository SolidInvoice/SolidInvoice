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

namespace SolidInvoice\EInvoiceBundle\Profile;

use SolidInvoice\EInvoiceBundle\Exception\UnknownProfileException;

/**
 * @see \SolidInvoice\EInvoiceBundle\Tests\Profile\ProfileRegistryTest
 */
final readonly class ProfileRegistry
{
    /**
     * @param iterable<ProfileInterface> $profiles
     */
    public function __construct(
        private iterable $profiles
    ) {
    }

    /**
     * @throws UnknownProfileException
     */
    public function get(string $identifier): ProfileInterface
    {
        foreach ($this->all() as $profile) {
            if ($profile->getIdentifier() === $identifier) {
                return $profile;
            }
        }

        throw UnknownProfileException::forIdentifier($identifier);
    }

    public function has(string $identifier): bool
    {
        return array_any($this->all(), fn (ProfileInterface $profile): bool => $profile->getIdentifier() === $identifier);
    }

    /**
     * @return list<ProfileInterface>
     */
    public function all(): array
    {
        return iterator_to_array($this->profiles, false);
    }
}
