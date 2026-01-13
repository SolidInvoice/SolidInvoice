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

use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

final readonly class OnboardingChecklistWidget implements WidgetInterface
{
    public function __construct(
        private ChecklistManager $checklistManager,
        private Security $security,
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

        if (! $this->checklistManager->shouldShow($user)) {
            return ['show' => false];
        }

        try {
            $progress = $this->checklistManager->getProgress();

            if (count($progress->items) === 0) {
                return ['show' => false];
            }
        } catch (Throwable) {
            return ['show' => false];
        }

        // Auto-dismiss if all items are complete (explicit side effect in widget, not manager)
        if ($progress->allComplete) {
            // $this->checklistManager->dismiss($user);
        }

        return [
            'show' => true,
            'progress' => $progress,
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/onboarding_checklist.html.twig';
    }
}
