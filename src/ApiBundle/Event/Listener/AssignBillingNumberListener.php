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

namespace SolidInvoice\ApiBundle\Event\Listener;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Ulid;

/**
 * Numbers an invoice or quote created without one, the way the web form does.
 *
 * PRE_VALIDATE rather than PRE_WRITE: the number has to exist before validation, so a
 * generated number goes through the same uniqueness check as a supplied one.
 */
final readonly class AssignBillingNumberListener implements EventSubscriberInterface
{
    public function __construct(
        private BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * @return array<string, list<list<string|int>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [['assignNumber', EventPriorities::PRE_VALIDATE]],
        ];
    }

    public function assignNumber(ViewEvent $event): void
    {
        $document = $event->getControllerResult();

        if (Request::METHOD_POST !== $event->getRequest()->getMethod() || ! $event->isMainRequest()) {
            return;
        }

        if ($document instanceof Invoice && ! $document->getId() instanceof Ulid && $document->getInvoiceId() === '') {
            $document->setInvoiceId($this->billingIdGenerator->generate($document, ['field' => 'invoiceId']));
        }

        if ($document instanceof Quote && ! $document->getId() instanceof Ulid && $document->getQuoteId() === '') {
            $document->setQuoteId($this->billingIdGenerator->generate($document, ['field' => 'quoteId']));
        }
    }
}
