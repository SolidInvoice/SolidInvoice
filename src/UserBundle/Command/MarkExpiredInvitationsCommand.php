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

namespace SolidInvoice\UserBundle\Command;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use function assert;
use function sprintf;

/**
 * @see \SolidInvoice\UserBundle\Tests\Command\MarkExpiredInvitationsCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:invitations:mark-expired',
    description: 'Flag user invitations that are past their validity period as expired',
)]
#[AsCronTask('#daily', schedule: 'mark_expired_invitations')]
final class MarkExpiredInvitationsCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly UserInvitationRepository $invitationRepository,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(UserInvitation::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Disable the company filter so invitations across all companies are processed.
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        try {
            $expired = $this->invitationRepository->markExpired(CarbonImmutable::now());
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf('Marked %d invitation(s) as expired.', $expired));

        return self::SUCCESS;
    }
}
