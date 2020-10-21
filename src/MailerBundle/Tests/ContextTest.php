<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Tests;

use ArrayAccess;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Context;

class ContextTest extends TestCase
{
    public function testContextExtendsArrayAccess()
    {
        static::assertInstanceOf(ArrayAccess::class, new Context());
    }

    public function testContextCreateReturnAnInstanceOfContext()
    {
        static::assertInstanceOf(Context::class, Context::create());
    }

    public function testContextCreateAddsTheCorrectProperties()
    {
        $context = Context::create(['foo' => 'bar']);
        static::assertSame('bar', $context['foo']);
    }

    public function testContextKeepsParameters()
    {
        $context = Context::create();

        $context['foo'] = 'bar';

        static::assertArrayHasKey('foo', $context);
        static::assertSame('bar', $context['foo']);
        unset($context['foo']);
        static::assertArrayNotHasKey('foo', $context);
    }
}
