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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Toggler\ToggleInterface;
use function bin2hex;
use function random_bytes;

/**
 * Resolves the internal {@see User} record that matches a verified
 * {@see GoogleIdentity}, linking or creating the record as needed.
 *
 * This is the single, shared entry point for turning a Google identity into a
 * SolidInvoice user. It is used by both the redirect-based OAuth login
 * (`OAuthAuthenticator`) and the Google One Tap endpoint.
 *
 * @see \SolidInvoice\UserBundle\Tests\OAuth\GoogleUserProvisionerTest
 */
final readonly class GoogleUserProvisioner implements GoogleUserProvisionerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ToggleInterface $toggle,
    ) {
    }

    public function findOrCreate(GoogleIdentity $identity, ?User $currentUser = null): ?ProvisionedUser
    {
        $repository = $this->entityManager->getRepository(User::class);

        $existing = $repository->findOneBy(['googleId' => $identity->googleId]);

        if ($existing instanceof User) {
            return new ProvisionedUser($existing, false);
        }

        $byEmail = $repository->findOneBy(['email' => $identity->email]);

        if ($byEmail instanceof User) {
            // Only ever attach a Google identity to a pre-existing account when
            // Google asserts the email address is verified. Without this check a
            // token carrying an unverified address could be used to take over an
            // account that happens to share that email.
            if (! $identity->emailVerified) {
                return null;
            }

            return $this->link($byEmail, $identity, false);
        }

        if ($currentUser instanceof User) {
            return $this->link($currentUser, $identity, false);
        }

        if (! $this->toggle->isActive('allow_registration')) {
            return null;
        }

        $user = new User();
        $user->setEmail($identity->email);
        $user->setFirstName($identity->firstName);
        $user->setLastName($identity->lastName);
        // A random, unusable password: these accounts authenticate through Google,
        // never with a password, but the column is non-null.
        $user->setPassword(bin2hex(random_bytes(24)));
        $user->setEnabled(true);
        $user->setVerified($identity->emailVerified);

        return $this->link($user, $identity, true);
    }

    private function link(User $user, GoogleIdentity $identity, bool $isNew): ProvisionedUser
    {
        $user->setGoogleId($identity->googleId);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new ProvisionedUser($user, $isNew);
    }
}
