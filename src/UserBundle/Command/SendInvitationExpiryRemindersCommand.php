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
use SolidInvoice\UserBundle\UserInvitation\UserInvitation as InvitationMailer;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use function assert;
use function sprintf;

/**
 * @see \SolidInvoice\UserBundle\Tests\Command\SendInvitationExpiryRemindersCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:invitations:send-expiry-reminders',
    description: 'Send a reminder email for invitations that are about to expire',
)]
#[AsCronTask('#daily', schedule: 'send_invitation_expiry_reminders')]
final class SendInvitationExpiryRemindersCommand extends Command
{
    /**
     * Number of days before expiry that the reminder is sent.
     */
    private const int REMINDER_DAYS_BEFORE = 2;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly UserInvitationRepository $invitationRepository,
        private readonly InvitationMailer $invitationMailer,
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

        $sent = 0;

        try {
            $now = CarbonImmutable::now();
            $threshold = $now->addDays(self::REMINDER_DAYS_BEFORE);

            foreach ($this->invitationRepository->findDueForExpiryReminder($now, $threshold) as $invitation) {
                $this->invitationMailer->sendExpiryReminder($invitation);
                $invitation->markReminderSent();
                ++$sent;
            }

            if ($sent > 0) {
                $entityManager->flush();
            }
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf('Sent %d invitation expiry reminder(s).', $sent));

        return self::SUCCESS;
    }
}
