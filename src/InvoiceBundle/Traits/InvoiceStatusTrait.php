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

namespace SolidInvoice\InvoiceBundle\Traits;

use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;

trait InvoiceStatusTrait
{
    abstract public function getStatus(): ?InvoiceStatus;

    public function isPaid(): bool
    {
        return InvoiceStatus::Paid === $this->getStatus();
    }

    public function isArchived(): bool
    {
        return InvoiceStatus::Archived === $this->getStatus();
    }

    public function isCancelled(): bool
    {
        return InvoiceStatus::Cancelled === $this->getStatus();
    }

    public function isDraft(): bool
    {
        return InvoiceStatus::Draft === $this->getStatus();
    }

    public function isNew(): bool
    {
        return InvoiceStatus::New === $this->getStatus();
    }

    public function isOverdue(): bool
    {
        return InvoiceStatus::Overdue === $this->getStatus();
    }

    public function isPending(): bool
    {
        return InvoiceStatus::Pending === $this->getStatus();
    }
}
