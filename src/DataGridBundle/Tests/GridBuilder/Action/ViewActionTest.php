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
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;

#[CoversClass(ViewAction::class)]
final class ViewActionTest extends TestCase
{
    public function testActionDefaults(): void
    {
        $action = ViewAction::new('route', ['param' => 'value']);

        self::assertSame('eye', $action->getIcon());
        self::assertSame('View', $action->getLabel());
        self::assertSame('route', $action->getRoute());
        self::assertSame(['param' => 'value'], $action->getParameters());
    }
}
