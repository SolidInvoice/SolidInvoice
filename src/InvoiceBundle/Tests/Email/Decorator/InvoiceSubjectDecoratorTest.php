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

namespace SolidInvoice\InvoiceBundle\Tests\Email\Decorator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Email\Decorator\InvoiceSubjectDecorator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\SettingsBundle\SystemConfig;

class InvoiceSubjectDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDecorate()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/email_subject')
            ->andReturn('New Invoice: #{id}');

        $decorator = new InvoiceSubjectDecorator($config);
        $message = new InvoiceEmail(new Invoice());
        $decorator->decorate(new MessageEvent($message, Context::create()));

        $this->assertSame('New Invoice: #', $message->getSubject());
    }

    public function testShouldDecorate()
    {
        $decorator = new InvoiceSubjectDecorator(M::mock(SystemConfig::class));

        $this->assertFalse($decorator->shouldDecorate(new MessageEvent(new \Swift_Message(), Context::create())));
        $this->assertFalse($decorator->shouldDecorate(new MessageEvent((new InvoiceEmail(new Invoice()))->setSubject('Invoice!'), Context::create())));
        $this->assertTrue($decorator->shouldDecorate(new MessageEvent(new InvoiceEmail(new Invoice()), Context::create())));
    }
}
