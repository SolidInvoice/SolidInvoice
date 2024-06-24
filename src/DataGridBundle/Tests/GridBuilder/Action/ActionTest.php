<?php

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
        $this->assertSame('Icon', $this->action->getIcon());
    }

    public function testRouteSetsAndGetsCorrectly(): void
    {
        $this->assertSame('route', $this->action->getRoute());
        $this->assertSame(['param' => 'value'], $this->action->getParameters());
    }

    public function testSetRoute(): void
    {
        $this->action->route('new route', ['new' => 'param']);

        $this->assertSame('new route', $this->action->getRoute());
        $this->assertSame(['new' => 'param'], $this->action->getParameters());
    }

    public function testLabelSetsAndGetsCorrectly(): void
    {
        $this->action->label('Label');
        $this->assertSame('Label', $this->action->getLabel());
    }
}
