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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\QuoteBundle\Email\Decorator\QuoteSubjectDecorator;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SettingsBundle\SystemConfig;

class QuoteSubjectDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDecorate()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/email_subject')
            ->andReturn('New Quote: #{id}');

        $decorator = new QuoteSubjectDecorator($config);
        $message = new QuoteEmail(new Quote());
        $decorator->decorate(new MessageEvent($message, Context::create()));

        $this->assertSame('New Quote: #', $message->getSubject());
    }

    public function testShouldDecorate()
    {
        $decorator = new QuoteSubjectDecorator(M::mock(SystemConfig::class));

        $this->assertFalse($decorator->shouldDecorate(new MessageEvent(new \Swift_Message(), Context::create())));
        $this->assertFalse($decorator->shouldDecorate(new MessageEvent((new QuoteEmail(new Quote()))->setSubject('Quote!'), Context::create())));
        $this->assertTrue($decorator->shouldDecorate(new MessageEvent(new QuoteEmail(new Quote()), Context::create())));
    }
}
