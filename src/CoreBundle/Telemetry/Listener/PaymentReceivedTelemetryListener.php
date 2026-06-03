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
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: PaymentEvents::PAYMENT_COMPLETE)]
final readonly class PaymentReceivedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(PaymentCompleteEvent $event): void
    {
        $this->telemetry->event(TelemetryEvent::PaymentReceived, [
            'gateway' => $event->getPayment()->getMethod()?->getGatewayName(),
        ]);
    }
}
