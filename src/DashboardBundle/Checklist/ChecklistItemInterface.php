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

/**
 * Interface for onboarding checklist items.
 * Each item represents a task that users should complete to get started.
 */
interface ChecklistItemInterface
{
    /**
     * Get the display name of the checklist item.
     */
    public function getName(): string;

    /**
     * Get a brief description of what this item entails.
     */
    public function getDescription(): string;

    /**
     * Get the Tabler icon name (e.g., 'tabler:upload').
     */
    public function getIcon(): string;

    /**
     * Get the route name to navigate to for completing this item.
     */
    public function getRoute(): string;

    /**
     * Get the priority for ordering (higher = shown first).
     */
    public function getPriority(): int;

    /**
     * Check if this item is completed for the current company context.
     * The company filter ensures only current company data is checked.
     */
    public function isComplete(): bool;

    /**
     * Whether this item should appear in the checklist.
     * Return false to hide the item without removing it from the codebase.
     */
    public function active(): bool;
}
