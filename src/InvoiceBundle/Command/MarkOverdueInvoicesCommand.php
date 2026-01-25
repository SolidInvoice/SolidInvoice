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

namespace SolidInvoice\InvoiceBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Message\MarkInvoiceOverdue;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use function assert;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:invoices:mark-overdue',
    description: 'Mark pending invoices as overdue when past due date',
)]
#[AsCronTask('#hourly', schedule: 'mark_invoices_overdue')]
final class MarkOverdueInvoicesCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(Invoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Disable company filter to query across all companies
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $processedCount = 0;
        $errorCount = 0;

        try {
            // Use toIterable() for memory-efficient iteration
            $overdueInvoices = $this->invoiceRepository->getPendingOverdueInvoices();

            foreach ($overdueInvoices as $invoice) {
                try {
                    // Dispatch async message with invoice ID and company ID
                    $this->bus->dispatch(new MarkInvoiceOverdue(
                        $invoice->getId(),
                        $invoice->getCompany()->getId()
                    ));

                    ++$processedCount;

                    $this->io->writeln(sprintf(
                        'Dispatched overdue processing for invoice %s (ID: %s)',
                        $invoice->getInvoiceId(),
                        $invoice->getId()->toString()
                    ));
                } catch (ExceptionInterface $e) {
                    ++$errorCount;
                    $this->io->error(sprintf(
                        'Error dispatching overdue processing for invoice %s: %s',
                        $invoice->getInvoiceId(),
                        $e->getMessage()
                    ));
                }

                // Detach entity to free memory
                $entityManager->detach($invoice);
            }
        } finally {
            // Re-enable company filter if it was enabled
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf(
            'Processed %d overdue invoices. Errors: %d',
            $processedCount,
            $errorCount
        ));

        return self::SUCCESS;
    }
}
