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
use SolidInvoice\MailerBundle\Configurator\GmailConfigurator;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\UsernamePasswordTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

class GmailConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('Gmail', (new GmailConfigurator())->getName());
    }

    public function testForm(): void
    {
        self::assertSame(UsernamePasswordTransportConfigType::class, (new GmailConfigurator())->getForm());
    }

    public function testConfigure(): void
    {
        self::assertEquals(Dsn::fromString('gmail+smtp://foo:bar@default'), (new GmailConfigurator())->configure(['username' => 'foo',  'password' => 'bar']));
    }
}
