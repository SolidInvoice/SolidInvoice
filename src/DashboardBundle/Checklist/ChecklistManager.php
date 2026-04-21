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

namespace SolidInvoice\DashboardBundle\Checklist;

use SolidInvoice\DashboardBundle\Checklist\DTO\ChecklistItemDTO;
use SolidInvoice\DashboardBundle\Checklist\DTO\ChecklistProgressDTO;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;

/**
 * Manages the onboarding checklist items and user progress.
 */
final readonly class ChecklistManager
{
    /**
     * @param iterable<ChecklistItemInterface> $items
     */
    public function __construct(
        private iterable $items,
        private UserSettingRepository $userSettingRepository,
    ) {
    }

    /**
     * Get all checklist items sorted by priority (highest first).
     *
     * @return array<ChecklistItemInterface>
     */
    public function getItems(): array
    {
        $items = array_filter([...$this->items], static fn (ChecklistItemInterface $item): bool => $item->active());
        usort($items, static fn (ChecklistItemInterface $a, ChecklistItemInterface $b): int => $b->getPriority() <=> $a->getPriority());

        return $items;
    }

    /**
     * Get checklist progress data for the current company.
     */
    public function getProgress(): ChecklistProgressDTO
    {
        $items = $this->getItems();
        $total = count($items);
        $completed = 0;

        $itemsData = [];
        foreach ($items as $item) {
            $isComplete = $item->isComplete();
            if ($isComplete) {
                ++$completed;
            }

            $itemsData[] = new ChecklistItemDTO(
                name: $item->getName(),
                description: $item->getDescription(),
                icon: $item->getIcon(),
                route: $item->getRoute(),
                completed: $isComplete,
            );
        }

        return new ChecklistProgressDTO(
            items: $itemsData,
            completed: $completed,
            total: $total,
            percentage: $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            allComplete: $completed === $total && $total > 0,
        );
    }

    /**
     * Check if the user has dismissed the checklist.
     */
    public function isDismissed(User $user): bool
    {
        $setting = $this->userSettingRepository->getSetting($user, UserSettingType::OnboardingChecklistDismissed);

        return $setting?->getValue() === 'true';
    }

    /**
     * Dismiss the checklist for a user.
     */
    public function dismiss(User $user): void
    {
        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::OnboardingChecklistDismissed,
            'true'
        );
    }

    /**
     * Check if the checklist should be shown to the user.
     * This is a pure read operation with no side effects.
     */
    public function shouldShow(User $user): bool
    {
        return ! $this->isDismissed($user);
    }
}
