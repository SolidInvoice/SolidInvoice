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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Action;

use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Action\Action
 */
final class ActionTest extends TestCase
{
    private Action $action;

    protected function setUp(): void
    {
        $this->action = Action::new('route', ['param' => 'value']);
    }

    public function testIconSetsAndGetsCorrectly(): void
    {
        $this->action->icon('Icon');
        self::assertSame('Icon', $this->action->getIcon());
    }

    public function testRouteSetsAndGetsCorrectly(): void
    {
        self::assertSame('route', $this->action->getRoute());
        self::assertSame(['param' => 'value'], $this->action->getParameters());
    }

    public function testSetRoute(): void
    {
        $this->action->route('new route', ['new' => 'param']);

        self::assertSame('new route', $this->action->getRoute());
        self::assertSame(['new' => 'param'], $this->action->getParameters());
    }

    public function testLabelSetsAndGetsCorrectly(): void
    {
        $this->action->label('Label');
        self::assertSame('Label', $this->action->getLabel());
    }

    public function testIsPrimaryByDefault(): void
    {
        self::assertTrue($this->action->isPrimary());
    }

    public function testInMenuMakesActionNotPrimary(): void
    {
        $this->action->inMenu();
        self::assertFalse($this->action->isPrimary());
    }

    public function testConfirmDisabledByDefault(): void
    {
        self::assertFalse($this->action->shouldConfirm());
    }

    public function testConfirmWithDefaultMessage(): void
    {
        $this->action->confirm();

        self::assertTrue($this->action->shouldConfirm());
        self::assertSame('Are you sure?', $this->action->getConfirmMessage());
    }

    public function testConfirmWithCustomMessage(): void
    {
        $this->action->confirm('Delete this item?');

        self::assertTrue($this->action->shouldConfirm());
        self::assertSame('Delete this item?', $this->action->getConfirmMessage());
    }

    public function testColorSetsAndGetsCorrectly(): void
    {
        $this->action->color('danger');
        self::assertSame('danger', $this->action->getColor());
    }

    public function testColorIsEmptyByDefault(): void
    {
        self::assertSame('', $this->action->getColor());
    }

    public function testFluentInterface(): void
    {
        $result = $this->action
            ->label('Edit')
            ->icon('pencil')
            ->color('primary')
            ->inMenu()
            ->confirm('Are you sure?');

        self::assertSame($this->action, $result);
        self::assertSame('Edit', $this->action->getLabel());
        self::assertSame('pencil', $this->action->getIcon());
        self::assertSame('primary', $this->action->getColor());
        self::assertFalse($this->action->isPrimary());
        self::assertTrue($this->action->shouldConfirm());
    }
}
