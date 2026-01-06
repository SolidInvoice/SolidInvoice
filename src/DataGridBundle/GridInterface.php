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

namespace SolidInvoice\DataGridBundle;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use Symfony\Component\Translation\TranslatableMessage;

interface GridInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function initialize(array $context): void;

    /**
     * @return class-string
     */
    public function entityFQCN(): string;

    /**
     * @return list<Column>
     */
    public function columns(): array;

    /**
     * @return list<Action>
     */
    public function actions(): array;

    /**
     * @return iterable<BatchAction>
     */
    public function batchActions(): iterable;

    /**
     * @return iterable<string, ColumnFilterInterface|null>
     */
    public function filters(): iterable;

    public function query(EntityManagerInterface $entityManager, Query $query): Query;

    /**
     * Returns the route name for creating a new entity.
     * Used for the empty state CTA button.
     */
    public function getCreateRoute(): ?string;

    /**
     * Returns the label for the create button in the empty state.
     */
    public function getCreateLabel(): ?TranslatableMessage;

    /**
     * Returns true if this grid supports expandable row details.
     */
    public function hasRowDetails(): bool;

    /**
     * Returns the template path for rendering expandable row details.
     * Only used if hasRowDetails() returns true.
     */
    public function getRowDetailTemplate(): ?string;
}
