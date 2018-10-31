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

namespace SolidInvoice\QuoteBundle\Tests\Email\Decorator;

use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\QuoteBundle\Email\Decorator\QuoteReceiverDecorator;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;

class QuoteReceiverDecoratorTest extends TestCase
{
    public function testDecorate()
    {
        $decorator = new QuoteReceiverDecorator();
        $quote = new Quote();
        $quote->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new QuoteEmail($quote);
        $decorator->decorate(new MessageEvent($message, Context::create()));

        $this->assertSame(['test@example.com' => 'Test User', 'another@example.com' => 'Another'], $message->getTo());
    }

    public function testShouldDecorate()
    {
        $decorator = new QuoteReceiverDecorator();

        $this->assertFalse($decorator->shouldDecorate(new MessageEvent(new \Swift_Message(), Context::create())));
        $this->assertFalse($decorator->shouldDecorate(new MessageEvent((new QuoteEmail(new Quote()))->addTo('info@example.com'), Context::create())));
        $this->assertTrue($decorator->shouldDecorate(new MessageEvent(new QuoteEmail(new Quote()), Context::create())));
    }
}
