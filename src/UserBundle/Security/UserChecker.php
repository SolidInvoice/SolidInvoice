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

namespace SolidInvoice\UserBundle\Security;

use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Blocks disabled accounts at authentication time.
 *
 * This is a core security property and applies to every install (hosted and
 * self-hosted) and every channel (web, REST API, MCP), since Symfony's default
 * user checker does not test {@see User::isEnabled()}.
 *
 * @see \SolidInvoice\UserBundle\Tests\Security\UserCheckerTest
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && ! $user->isEnabled()) {
            throw new DisabledException('Your account is disabled.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // No-op: disabled accounts are rejected in checkPreAuth().
    }
}
