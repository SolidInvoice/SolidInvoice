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
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction
 */
final class EditActionTest extends TestCase
{
    public function testActionDefaults(): void
    {
        $action = EditAction::new('route', ['param' => 'value']);

        $this->assertSame('pencil', $action->getIcon());
        $this->assertSame('Edit', $action->getLabel());
        $this->assertSame('route', $action->getRoute());
        $this->assertSame(['param' => 'value'], $action->getParameters());
    }
}
