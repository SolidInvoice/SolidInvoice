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

namespace SolidInvoice\ApiBundle\Security\Voter;

use SolidInvoice\ApiBundle\Security\Attribute;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Default access voter for the REST API. Grants when the SaaS feature is
 * disabled (self-hosted installs) so requests succeed without any SaaS
 * voter being registered. When SaaS is enabled it abstains, leaving the
 * decision to whichever subscription-aware voter has been registered.
 */
final class ApiAccessVoter extends Voter
{
    public function __construct(
        private readonly ToggleInterface $toggler,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Abstain on SaaS deployments — SubscriptionVoter is responsible there.
        return $attribute === Attribute::ACCESS && ! $this->toggler->isActive('saas_enabled');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return true;
    }
}
