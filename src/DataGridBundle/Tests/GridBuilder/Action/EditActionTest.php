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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;

#[CoversClass(EditAction::class)]
final class EditActionTest extends TestCase
{
    public function testActionDefaults(): void
    {
        $action = EditAction::new('route', ['param' => 'value']);

        self::assertSame('pencil', $action->getIcon());
        self::assertSame('Edit', $action->getLabel());
        self::assertSame('route', $action->getRoute());
        self::assertSame(['param' => 'value'], $action->getParameters());
    }
}
