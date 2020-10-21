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

namespace SolidInvoice\MailerBundle\Tests\Decorator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Decorator\HtmlTemplateDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\MailerBundle\Template\HtmlTemplateMessage;
use SolidInvoice\MailerBundle\Template\Template;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class HtmlTemplateDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testShouldDecorateWithStandardMessage()
    {
        $config = M::mock(SystemConfig::class);
        $decorator = new HtmlTemplateDecorator($config, new Environment(new ArrayLoader()));

        static::assertFalse($decorator->shouldDecorate(new MessageEvent(new \Swift_Message(), Context::create())));
    }

    public function testShouldDecorateWithTextConfig()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('text');

        $decorator = new HtmlTemplateDecorator($config, new Environment(new ArrayLoader()));

        static::assertFalse($decorator->shouldDecorate(new MessageEvent(new class() extends \Swift_Message implements HtmlTemplateMessage {
            public function getHtmlTemplate(): Template
            {
                return new Template('');
            }
        }, Context::create())));
    }

    public function testShouldDecorateWithHtmlConfig()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('html');

        $decorator = new HtmlTemplateDecorator($config, new Environment(new ArrayLoader()));

        static::assertTrue($decorator->shouldDecorate(new MessageEvent(new class() extends \Swift_Message implements HtmlTemplateMessage {
            public function getHtmlTemplate(): Template
            {
                return new Template('');
            }
        }, Context::create())));
    }

    public function testShouldDecorateWithBothConfig()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('both');

        $decorator = new HtmlTemplateDecorator($config, new Environment(new ArrayLoader()));

        static::assertTrue($decorator->shouldDecorate(new MessageEvent(new class() extends \Swift_Message implements HtmlTemplateMessage {
            public function getHtmlTemplate(): Template
            {
                return new Template('');
            }
        }, Context::create())));
    }

    public function testDecorate()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('both');

        $twig = new Environment(new ArrayLoader(['@SolidInvoice/email.html.twig' => 'HTML Template']));

        $decorator = new HtmlTemplateDecorator($config, $twig);

        $message = new class() extends \Swift_Message implements HtmlTemplateMessage {
            public function getHtmlTemplate(): Template
            {
                return new Template('@SolidInvoice/email.html.twig', ['a' => 'b']);
            }
        };

        $decorator->decorate(new MessageEvent($message, Context::create(['c' => 'd'])));

        static::assertSame('HTML Template', $message->getBody());
    }
}
