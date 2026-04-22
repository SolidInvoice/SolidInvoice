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

namespace SolidInvoice\DashboardBundle\Checklist\Items;

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;

final readonly class SendInvoiceItem implements ChecklistItemInterface
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.send_invoice.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.send_invoice.description';
    }

    public function getIcon(): string
    {
        return 'tabler:file-invoice';
    }

    public function getRoute(): string
    {
        return '_invoices_create';
    }

    public function getPriority(): int
    {
        return -400;
    }

    public function active(): bool
    {
        return true;
    }

    public function isComplete(): bool
    {
        // Check if there's at least one invoice that has been sent (not draft)
        // Company filter ensures we only count invoices for the current company
        $totalInvoices = $this->invoiceRepository->count([]);
        $draftInvoices = $this->invoiceRepository->getCountByStatus(InvoiceStatus::Draft);

        // At least one invoice exists AND not all invoices are drafts
        return $totalInvoices > 0 && $draftInvoices < $totalInvoices;
    }
}
