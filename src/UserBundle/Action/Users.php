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

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bridge\Twig\Attribute\Template;

final class Users
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserInvitationRepository $invitationRepository,
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
    ) {
    }

    /**
     * @return array{totalActiveUsers: int, totalPendingInvitations: int, recentlyJoinedCount: int, seatsUsage: int}
     */
    #[Template('@SolidInvoiceUser/Users/index.html.twig')]
    public function __invoke(): array
    {
        $totalActiveUsers = $this->userRepository->getUserCount();
        $totalPendingInvitations = $this->invitationRepository->countPendingInvitations();
        $recentlyJoinedCount = $this->userRepository->getRecentlyJoinedCount(30);

        $company = $this->companyRepository->find($this->companySelector->getCompany());
        $seatsUsage = $company instanceof Company
            ? $this->userRepository->getUserCountForCompany($company)
                + $this->invitationRepository->countPending($company)
            : 0;

        return [
            'totalActiveUsers' => $totalActiveUsers,
            'totalPendingInvitations' => $totalPendingInvitations,
            'recentlyJoinedCount' => $recentlyJoinedCount,
            'seatsUsage' => $seatsUsage,
        ];
    }
}
