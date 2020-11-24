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

namespace SolidInvoice\MailerBundle\Tests\Configurator;

use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Configurator\GmailConfigurator;
use SolidInvoice\MailerBundle\Configurator\MailchimpConfigurator;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\KeyTransportConfigType;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\UsernamePasswordTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

class MailchimpConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('Mailchimp Mandrill', (new MailchimpConfigurator())->getName());
    }

    public function testForm(): void
    {
        self::assertSame(KeyTransportConfigType::class, (new MailchimpConfigurator())->getForm());
    }

    public function testConfigure(): void
    {
        self::assertEquals(Dsn::fromString('mandrill+api://foobar@default'), (new MailchimpConfigurator())->configure(['key' => 'foobar']));
    }
}