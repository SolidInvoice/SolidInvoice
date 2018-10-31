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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\InvoiceBundle\Email\Decorator\InvoiceReceiverDecorator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\SettingsBundle\SystemConfig;

class InvoiceReceiverDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDecorateWithoutBcc()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->andReturnNull();

        $decorator = new InvoiceReceiverDecorator($config);
        $invoice = new Invoice();
        $invoice->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $invoice->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new InvoiceEmail($invoice);
        $decorator->decorate(new MessageEvent($message, Context::create()));

        $this->assertSame(['test@example.com' => 'Test User', 'another@example.com' => 'Another'], $message->getTo());
        $this->assertNull($message->getBcc());
    }

    public function testDecorateWithBcc()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->andReturn('bcc@example.com');

        $decorator = new InvoiceReceiverDecorator($config);
        $invoice = new Invoice();
        $invoice->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $invoice->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new InvoiceEmail($invoice);
        $decorator->decorate(new MessageEvent($message, Context::create()));

        $this->assertSame(['test@example.com' => 'Test User', 'another@example.com' => 'Another'], $message->getTo());
        $this->assertSame(['bcc@example.com' => null], $message->getBcc());
    }

    public function testShouldDecorate()
    {
        $decorator = new InvoiceReceiverDecorator(M::mock(SystemConfig::class));

        $this->assertFalse($decorator->shouldDecorate(new MessageEvent(new \Swift_Message(), Context::create())));
        $this->assertFalse($decorator->shouldDecorate(new MessageEvent((new InvoiceEmail(new Invoice()))->addTo('info@example.com'), Context::create())));
        $this->assertTrue($decorator->shouldDecorate(new MessageEvent(new InvoiceEmail(new Invoice()), Context::create())));
    }
}
