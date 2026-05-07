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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

final readonly class RecurringInvoiceVerificationGuardListener
{
    public function __construct(
        private EmailVerificationGateInterface $gate,
    ) {
    }

    #[AsEventListener('workflow.recurring_invoice.guard.activate')]
    public function onGuardActivate(GuardEvent $event): void
    {
        if (! $this->gate->isGated()) {
            return;
        }

        $event->addTransitionBlocker(
            TransitionBlocker::createUnknown(
                $this->gate->reason('activating this recurring invoice'),
            ),
        );
    }
}
