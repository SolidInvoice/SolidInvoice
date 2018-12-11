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

namespace SolidInvoice\CoreBundle\Tests\Swiftmailer\Plugin;

use SolidInvoice\CoreBundle\Swiftmailer\Plugin\CssInlinerPlugin;
use PHPUnit\Framework\TestCase;
use Mockery as M;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CssInlinerPluginTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItOnlyDecoratesHtml()
    {
        $inliner = M::mock(CssToInlineStyles::class);
        $plugin = new CssInlinerPlugin($inliner);

        $inliner->shouldReceive('convert')
            ->once()
            ->with('<p>Hello World!<p/>')
            ->andReturn('<p>Hello World!<p/>');

        $message = new \Swift_Message();
        $message->setBody('<p>Hello World!<p/>', 'text/html');
        $event = new \Swift_Events_SendEvent(M::mock(\Swift_Transport::class), $message);

        $plugin->beforeSendPerformed($event);
    }

    public function testItDoesNotDecorateText()
    {
        $inliner = M::mock(CssToInlineStyles::class);
        $plugin = new CssInlinerPlugin($inliner);

        $inliner->shouldNotReceive('convert')
            ->with('Hello World!');

        $message = new \Swift_Message();
        $message->setBody('Hello World!', 'text/plain');
        $event = new \Swift_Events_SendEvent(M::mock(\Swift_Transport::class), $message);

        $plugin->beforeSendPerformed($event);
    }
}
