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

namespace SolidInvoice\InvoiceBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<InvoiceReminder>
 */
class InvoiceReminderRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceReminder::class);
    }

    /**
     * Check if a specific reminder type has been sent for an invoice.
     */
    public function hasReminderBeenSent(Invoice $invoice, ReminderType $reminderType): bool
    {
        return null !== $this->findOneBy([
            'invoice' => $invoice,
            'reminderType' => $reminderType,
        ]);
    }

    /**
     * Get all reminders sent for an invoice.
     *
     * @return InvoiceReminder[]
     */
    public function getReminderHistory(Invoice $invoice): array
    {
        return $this->findBy(
            ['invoice' => $invoice],
            ['sentAt' => 'ASC']
        );
    }
}
