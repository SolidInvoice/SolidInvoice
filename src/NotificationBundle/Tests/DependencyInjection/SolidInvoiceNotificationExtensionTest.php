<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use SolidInvoice\NotificationBundle\DependencyInjection\SolidInvoiceNotificationExtension;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;

class SolidInvoiceNotificationExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new SolidInvoiceNotificationExtension()];
    }

    public function testLoad(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(NotificationManager::class);
    }
}
