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

use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Telemetry\Listener\InvoiceCreatedTelemetryListenerTest
 */
#[AsEventListener(event: InvoiceEvents::INVOICE_POST_CREATE)]
final readonly class InvoiceCreatedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(InvoiceEvent $event): void
    {
        $this->telemetry->event(TelemetryEvent::InvoiceCreated);
    }
}
