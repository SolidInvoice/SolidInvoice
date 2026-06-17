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

use Override;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Extends the global disabled-account check with an unverified-email block.
 *
 * Wired onto the stateless API and MCP firewalls so that, on hosted
 * (`saas_enabled`) deployments, an unverified user is hard-blocked from those
 * channels regardless of a valid token. Unverified web users are NOT blocked —
 * they keep full login and are nudged by the email-verification banner while the
 * verification gate ({@see \SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface})
 * limits sensitive actions such as sending invoices.
 *
 * @see \SolidInvoice\UserBundle\Tests\Security\VerifiedUserCheckerTest
 */
final class VerifiedUserChecker extends UserChecker
{
    public function __construct(
        private readonly ToggleInterface $toggle,
    ) {
    }

    #[Override]
    public function checkPostAuth(UserInterface $user): void
    {
        parent::checkPostAuth($user);

        if (! $this->toggle->isActive('saas_enabled')) {
            return;
        }

        if ($user instanceof User && ! $user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Please verify your email address before continuing.');
        }
    }
}
