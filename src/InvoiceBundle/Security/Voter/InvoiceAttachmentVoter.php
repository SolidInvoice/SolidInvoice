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

namespace SolidInvoice\InvoiceBundle\Security\Voter;

use SensitiveParameter;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Uid\Ulid;

/**
 * Unlike `ExportJobVoter`, every authorised user in the company may view and manage
 * their company's attachments — an invoice is shared work, not restricted to whoever
 * uploaded the file.
 *
 * @extends Voter<string, InvoiceAttachment>
 * @see \SolidInvoice\InvoiceBundle\Tests\Security\Voter\InvoiceAttachmentVoterTest
 */
final class InvoiceAttachmentVoter extends Voter
{
    public const string ATTACHMENT_VIEW = 'ATTACHMENT_VIEW';

    public const string ATTACHMENT_UPLOAD = 'ATTACHMENT_UPLOAD';

    public const string ATTACHMENT_DELETE = 'ATTACHMENT_DELETE';

    private const array ATTRIBUTES = [self::ATTACHMENT_VIEW, self::ATTACHMENT_UPLOAD, self::ATTACHMENT_DELETE];

    public function __construct(
        private readonly CompanySelector $companySelector,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof InvoiceAttachment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, #[SensitiveParameter] TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (! $user instanceof User) {
            return false;
        }

        assert($subject instanceof InvoiceAttachment);

        $activeCompanyId = $this->companySelector->getCompany();
        if (! $activeCompanyId instanceof Ulid) {
            return false;
        }

        return $subject->getCompany()->getId()->equals($activeCompanyId);
    }
}
