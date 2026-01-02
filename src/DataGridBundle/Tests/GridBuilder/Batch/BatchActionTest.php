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

    public function testNewSetsLabel(): void
    {
        self::assertSame('Test', $this->batchAction->getLabel());
    }

    public function testConfirmIsTrueByDefault(): void
    {
        self::assertTrue($this->batchAction->shouldConfirm());
    }

    public function testConfirmCanBeDisabled(): void
    {
        $this->batchAction->confirm(false);
        self::assertFalse($this->batchAction->shouldConfirm());
    }

    public function testConfirmCanBeReEnabled(): void
    {
        $this->batchAction->confirm(false);
        $this->batchAction->confirm(true);
        self::assertTrue($this->batchAction->shouldConfirm());
    }

    public function testConfirmMessageIsEmptyByDefault(): void
    {
        self::assertSame('', $this->batchAction->getConfirmMessage());
    }

    public function testConfirmMessageSetsAndGetsCorrectly(): void
    {
        $this->batchAction->confirmMessage('Are you sure you want to delete these items?');
        self::assertSame('Are you sure you want to delete these items?', $this->batchAction->getConfirmMessage());
    }

    public function testActionIsNullByDefault(): void
    {
        self::assertNull($this->batchAction->getAction());
    }

    public function testActionSetsAndGetsCorrectly(): void
    {
        $action = static fn () => 'Action';
        $this->batchAction->action($action);
        self::assertIsCallable($this->batchAction->getAction());
    }

    public function testActionIsConvertedToClosure(): void
    {
        $action = static fn () => 'result';
        $this->batchAction->action($action);

        $closure = $this->batchAction->getAction();
        self::assertInstanceOf(\Closure::class, $closure);
        self::assertSame('result', $closure());
    }

    public function testRouteIsEmptyByDefault(): void
    {
        self::assertSame('', $this->batchAction->getRoute());
        self::assertSame([], $this->batchAction->getRouteParameters());
    }

    public function testRouteSetsAndGetsCorrectly(): void
    {
        $this->batchAction->route('route', ['param' => 'value']);
        self::assertSame('route', $this->batchAction->getRoute());
        self::assertSame(['param' => 'value'], $this->batchAction->getRouteParameters());
    }

    public function testRouteWithDefaultParameters(): void
    {
        $this->batchAction->route('route');
        self::assertSame('route', $this->batchAction->getRoute());
        self::assertSame([], $this->batchAction->getRouteParameters());
    }

    public function testLabelSetsAndGetsCorrectly(): void
    {
        $this->batchAction->label('Label');
        self::assertSame('Label', $this->batchAction->getLabel());
    }

    public function testIconIsEmptyByDefault(): void
    {
        self::assertSame('', $this->batchAction->getIcon());
    }

    public function testIconSetsAndGetsCorrectly(): void
    {
        $this->batchAction->icon('trash');
        self::assertSame('trash', $this->batchAction->getIcon());
    }

    public function testColorIsEmptyByDefault(): void
    {
        self::assertSame('', $this->batchAction->getColor());
    }

    public function testColorSetsAndGetsCorrectly(): void
    {
        $this->batchAction->color('danger');
        self::assertSame('danger', $this->batchAction->getColor());
    }

    public function testFluentInterface(): void
    {
        $result = $this->batchAction
            ->label('Delete')
            ->icon('trash')
            ->color('danger')
            ->confirm(true)
            ->confirmMessage('Delete selected items?')
            ->route('delete_route', ['ids' => 'selected']);

        self::assertSame($this->batchAction, $result);
        self::assertSame('Delete', $this->batchAction->getLabel());
        self::assertSame('trash', $this->batchAction->getIcon());
        self::assertSame('danger', $this->batchAction->getColor());
        self::assertTrue($this->batchAction->shouldConfirm());
        self::assertSame('Delete selected items?', $this->batchAction->getConfirmMessage());
        self::assertSame('delete_route', $this->batchAction->getRoute());
    }
}
