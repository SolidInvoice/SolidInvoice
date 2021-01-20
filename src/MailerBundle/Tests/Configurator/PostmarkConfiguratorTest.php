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

namespace SolidInvoice\MailerBundle\Tests\Configurator;

use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Configurator\PostmarkConfigurator;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\KeyTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

class PostmarkConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('Postmark', (new PostmarkConfigurator())->getName());
    }

    public function testForm(): void
    {
        self::assertSame(KeyTransportConfigType::class, (new PostmarkConfigurator())->getForm());
    }

    public function testConfigure(): void
    {
        self::assertEquals(Dsn::fromString('postmark+api://foobar@default'), (new PostmarkConfigurator())->configure(['key' => 'foobar']));
    }
}
