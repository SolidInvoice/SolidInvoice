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

namespace SolidInvoice\NotificationBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Namshi\Notificator\Manager;
use Namshi\Notificator\Notification\Handler\SwiftMailer;
use SolidInvoice\NotificationBundle\DependencyInjection\SolidInvoiceNotificationExtension;
use SolidInvoice\NotificationBundle\Notification\Factory;
use SolidInvoice\NotificationBundle\Notification\Handler\ChainedHandler;
use SolidInvoice\NotificationBundle\Notification\Handler\TwilioHandler;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Twilio\Rest\Client;

class SolidInvoiceNotificationExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new SolidInvoiceNotificationExtension()];
    }

    public function testLoad()
    {
        $this->load();

        $this->assertContainerBuilderHasService(Manager::class);
        $this->assertContainerBuilderHasService(NotificationManager::class);
        $this->assertContainerBuilderHasService(Factory::class);
        $this->assertContainerBuilderHasService(Client::class);

        $this->assertContainerBuilderHasService(ChainedHandler::class);
        $this->assertContainerBuilderHasService(TwilioHandler::class);
        $this->assertContainerBuilderHasService(SwiftMailer::class);
    }
}
