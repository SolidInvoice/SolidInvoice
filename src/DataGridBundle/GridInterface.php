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

use Doctrine\Persistence\ObjectManager;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use Symfony\Component\HttpFoundation\Request;

interface GridInterface
{
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
     * @return list<BatchAction>
     */
    public function batchActions(): array;

    /*public function requiresStatus(): bool;

    /* *
     * @return mixed
     * /
    public function fetchData(Request $request, ObjectManager $em): array;

    public function setParameters(array $params);*/
}
