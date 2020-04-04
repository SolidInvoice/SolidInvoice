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
use SolidInvoice\MailerBundle\Decorator\TextTemplateDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\MailerBundle\Template\Template;
use SolidInvoice\MailerBundle\Template\TextTemplateMessage;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Templating\EngineInterface;

class TextTemplateDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testShouldDecorateWithStandardMessage()
    {
        $config = M::mock(SystemConfig::class);
        $decorator = new TextTemplateDecorator($config, M::mock(EngineInterface::class));

        $this->assertFalse($decorator->shouldDecorate(new MessageEvent(new \Swift_Message(), Context::create())));
    }

    public function testShouldDecorateWithHtmlConfig()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('html');

        $decorator = new TextTemplateDecorator($config, M::mock(EngineInterface::class));

        $this->assertFalse($decorator->shouldDecorate(new MessageEvent(new class() extends \Swift_Message implements TextTemplateMessage {
            public function getTextTemplate(): Template
            {
            }
        }, Context::create())));
    }

    public function testShouldDecorateWithTextConfig()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('text');

        $decorator = new TextTemplateDecorator($config, M::mock(EngineInterface::class));

        $this->assertTrue($decorator->shouldDecorate(new MessageEvent(new class() extends \Swift_Message implements TextTemplateMessage {
            public function getTextTemplate(): Template
            {
            }
        }, Context::create())));
    }

    public function testShouldDecorateWithBothConfig()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('both');

        $decorator = new TextTemplateDecorator($config, M::mock(EngineInterface::class));

        $this->assertTrue($decorator->shouldDecorate(new MessageEvent(new class() extends \Swift_Message implements TextTemplateMessage {
            public function getTextTemplate(): Template
            {
            }
        }, Context::create())));
    }

    public function testDecorateWithText()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('text');

        $engine = M::mock(EngineInterface::class);
        $engine->shouldReceive('render')
            ->once()
            ->with('@SolidInvoice/email.txt.twig', ['a' => 'b', 'c' => 'd'])
            ->andReturn('Text Template');

        $decorator = new TextTemplateDecorator($config, $engine);

        $message = new class() extends \Swift_Message implements TextTemplateMessage {
            public function getTextTemplate(): Template
            {
                return new Template('@SolidInvoice/email.txt.twig', ['a' => 'b']);
            }
        };

        $decorator->decorate(new MessageEvent($message, Context::create(['c' => 'd'])));

        $this->assertSame('Text Template', $message->getBody());
    }

    public function testDecorateWithBoth()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('email/format')
            ->andReturn('both');

        $engine = M::mock(EngineInterface::class);
        $engine->shouldReceive('render')
            ->once()
            ->with('@SolidInvoice/email.txt.twig', ['a' => 'b', 'c' => 'd'])
            ->andReturn('Text Template');

        $decorator = new TextTemplateDecorator($config, $engine);

        $message = new class() extends \Swift_Message implements TextTemplateMessage {
            public function getTextTemplate(): Template
            {
                return new Template('@SolidInvoice/email.txt.twig', ['a' => 'b']);
            }
        };

        $decorator->decorate(new MessageEvent($message, Context::create(['c' => 'd'])));

        $this->assertSame('Text Template', $message->getChildren()[0]->getBody());
    }
}
