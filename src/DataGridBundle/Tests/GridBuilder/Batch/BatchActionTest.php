<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Batch;

use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction
 */
final class BatchActionTest extends TestCase
{
    private BatchAction $batchAction;

    protected function setUp(): void
    {
        $this->batchAction = BatchAction::new('Test');
    }

    public function testConfirmSetsAndGetsCorrectly(): void
    {
        $this->batchAction->confirm(false);
        $this->assertFalse($this->batchAction->shouldConfirm());
    }

    public function testActionSetsAndGetsCorrectly(): void
    {
        $action = static fn () => 'Action';
        $this->batchAction->action($action);
        $this->assertSame($action, $this->batchAction->getAction());
    }

    public function testRouteSetsAndGetsCorrectly(): void
    {
        $this->batchAction->route('route', ['param' => 'value']);
        $this->assertSame('route', $this->batchAction->getRoute());
        $this->assertSame(['param' => 'value'], $this->batchAction->getRouteParameters());
    }

    public function testLabelSetsAndGetsCorrectly(): void
    {
        $this->batchAction->label('Label');
        $this->assertSame('Label', $this->batchAction->getLabel());
    }

    public function testIconSetsAndGetsCorrectly(): void
    {
        $this->batchAction->icon('Icon');
        $this->assertSame('Icon', $this->batchAction->getIcon());
    }

    public function testColorSetsAndGetsCorrectly(): void
    {
        $this->batchAction->color('Color');
        $this->assertSame('Color', $this->batchAction->getColor());
    }
}
