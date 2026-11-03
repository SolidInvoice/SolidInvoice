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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ApiBundle\DTO\MarkSentInput;
use SolidInvoice\CoreBundle\Event\DocumentSentEvent;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * Records that the invoice reached the client outside the app (a shared link, a message, on
 * paper): `accept` without an email. On an invoice that is already out it only records the share.
 *
 * @implements ProcessorInterface<MarkSentInput, Invoice>
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\Api\InvoiceSendTest
 */
final readonly class InvoiceMarkSentProcessor implements ProcessorInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private WorkflowInterface $invoiceStateMachine,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Invoice
    {
        assert($data instanceof MarkSentInput);

        $invoice = $this->registry->getRepository(Invoice::class)->findOneBy(['id' => $uriVariables['id'] ?? null]);

        if (! $invoice instanceof Invoice) {
            throw new NotFoundHttpException(sprintf('Invoice "%s" not found.', $uriVariables['id'] ?? ''));
        }

        match ($invoice->getStatus()) {
            InvoiceStatus::New, InvoiceStatus::Draft => $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ACCEPT),
            InvoiceStatus::Pending, InvoiceStatus::Overdue => null,
            default => throw new ConflictHttpException(sprintf('An invoice in status "%s" cannot be marked as sent.', $invoice->getStatus()->value ?? 'unknown')),
        };

        $this->registry->getManager()->flush();

        $this->eventDispatcher->dispatch(new DocumentSentEvent($invoice, $data->channel));

        return $invoice;
    }
}
