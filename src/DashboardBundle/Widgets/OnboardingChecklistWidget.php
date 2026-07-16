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

namespace SolidInvoice\DashboardBundle\Widgets;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @see \SolidInvoice\DashboardBundle\Tests\Widgets\OnboardingChecklistWidgetTest
 */
final readonly class OnboardingChecklistWidget implements WidgetInterface
{
    public function __construct(
        private ChecklistManager $checklistManager,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return ['show' => false];
        }

        try {
            // Both calls read from the database, so both belong inside the guard:
            // shouldShow() reads the user's dismissal setting, and getProgress()
            // runs every item's isComplete() check.
            if (! $this->checklistManager->shouldShow($user)) {
                return ['show' => false];
            }

            $progress = $this->checklistManager->getProgress();
        } catch (DBALException | ORMException $e) {
            $this->logger->error('Unable to load the onboarding checklist progress', ['exception' => $e]);

            // Render an error state rather than vanishing: a checklist that
            // silently disappears is indistinguishable from a completed one.
            return ['show' => true, 'error' => true];
        }

        if ([] === $progress->items) {
            return ['show' => false];
        }

        return [
            'show' => true,
            'error' => false,
            'progress' => $progress,
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/onboarding_checklist.html.twig';
    }
}
