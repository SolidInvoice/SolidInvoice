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

namespace SolidInvoice\CoreBundle\Export\Security\Voter;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Uid\Ulid;

/**
 * @extends Voter<string, ExportJob>
 * @see \SolidInvoice\CoreBundle\Tests\Export\Security\Voter\ExportJobVoterTest
 */
final class ExportJobVoter extends Voter
{
    public const string DOWNLOAD = 'EXPORT_DOWNLOAD';

    public function __construct(
        private readonly CompanySelector $companySelector,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DOWNLOAD && $subject instanceof ExportJob;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (! $user instanceof User) {
            return false;
        }

        assert($subject instanceof ExportJob);

        if (! $subject->getRequestedBy()->equals($user->getId())) {
            return false;
        }

        $activeCompanyId = $this->companySelector->getCompany();
        if (! $activeCompanyId instanceof Ulid) {
            return false;
        }

        return $subject->getCompany()->getId()->equals($activeCompanyId);
    }
}
