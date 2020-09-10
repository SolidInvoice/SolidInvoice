<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Traits;

use SolidInvoice\InvoiceBundle\Model\Graph;

trait InvoiceStatusTrait
{
    abstract public function getStatus();

    public function isPaid(): bool
    {
        return Graph::STATUS_PAID === $this->getStatus();
    }

    public function isArchived(): bool
    {
        return Graph::STATUS_ARCHIVED === $this->getStatus();
    }

    public function isCancelled(): bool
    {
        return Graph::STATUS_CANCELLED === $this->getStatus();
    }

    public function isDraft(): bool
    {
        return Graph::STATUS_DRAFT === $this->getStatus();
    }

    public function isNew(): bool
    {
        return Graph::STATUS_NEW === $this->getStatus();
    }

    public function isOverdue(): bool
    {
        return Graph::STATUS_OVERDUE === $this->getStatus();
    }

    public function isPending(): bool
    {
        return Graph::STATUS_PENDING === $this->getStatus();
    }
}
