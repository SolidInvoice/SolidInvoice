<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Tests\Listener;

use Carbon\Carbon;
use SolidInvoice\SettingsBundle\Listener\MailerSettingsEnvListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Mockery as M;
use PHPUnit\Framework\TestCase;

class MailerSettingsEnvListenerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testApplicationNotInstalled()
    {
        $config = M::mock(SystemConfig::class);

        $listener = new MailerSettingsEnvListener(null, $config);

        $this->assertNull($listener->onKernelRequest());

        $config->shouldNotHaveReceived('getAll');
    }

    public function testSetEnvValues()
    {
        $this->assertFalse(getenv('mailer_host'));
        $this->assertFalse(getenv('mailer_transport'));

        $config = M::mock(SystemConfig::class);

        $config->shouldReceive('getAll')
            ->withNoArgs()
            ->andReturn([
                'email/sending_options/host' => 'smtp.example.com',
                'email/sending_options/transport' => 'smtp',
                'invalid/settings/option' => 'foobar',
            ]);

        $listener = new MailerSettingsEnvListener((string) Carbon::now(), $config);

        $listener->onKernelRequest();

        $this->assertSame('smtp.example.com', getenv('mailer_host'));
        $this->assertSame('smtp', getenv('mailer_transport'));
        $this->assertFalse(getenv('option'));
    }
}
