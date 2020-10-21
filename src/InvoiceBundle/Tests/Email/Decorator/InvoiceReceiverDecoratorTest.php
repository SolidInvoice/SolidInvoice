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
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Email\Decorator\InvoiceReceiverDecorator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\SettingsBundle\SystemConfig;
use Swift_Message;

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

        static::assertSame(['test@example.com' => 'Test User', 'another@example.com' => 'Another'], $message->getTo());
        static::assertNull($message->getBcc());
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

        static::assertSame(['test@example.com' => 'Test User', 'another@example.com' => 'Another'], $message->getTo());
        static::assertSame(['bcc@example.com' => null], $message->getBcc());
    }

    public function testShouldDecorate()
    {
        $decorator = new InvoiceReceiverDecorator(M::mock(SystemConfig::class));

        static::assertFalse($decorator->shouldDecorate(new MessageEvent(new Swift_Message(), Context::create())));
        static::assertFalse($decorator->shouldDecorate(new MessageEvent((new InvoiceEmail(new Invoice()))->addTo('info@example.com'), Context::create())));
        static::assertTrue($decorator->shouldDecorate(new MessageEvent(new InvoiceEmail(new Invoice()), Context::create())));
    }
}
