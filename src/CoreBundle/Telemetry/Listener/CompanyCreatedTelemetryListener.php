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

namespace SolidInvoice\CoreBundle\Telemetry\Listener;

use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(CompanyCreatedEvent::class)]
final readonly class CompanyCreatedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(CompanyCreatedEvent $event): void
    {
        $this->telemetry->event(TelemetryEvent::CompanyCreated);
    }
}
