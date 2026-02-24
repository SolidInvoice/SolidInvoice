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

namespace SolidInvoice\QuoteBundle\Traits;

use SolidInvoice\QuoteBundle\Enum\QuoteStatus;

trait QuoteStatusTrait
{
    abstract public function getStatus(): ?QuoteStatus;

    public function isPending(): bool
    {
        return QuoteStatus::Pending === $this->getStatus();
    }

    public function isDraft(): bool
    {
        return QuoteStatus::Draft === $this->getStatus();
    }

    public function isCancelled(): bool
    {
        return QuoteStatus::Cancelled === $this->getStatus();
    }

    public function isAccepted(): bool
    {
        return QuoteStatus::Accepted === $this->getStatus();
    }

    public function isArchived(): bool
    {
        return QuoteStatus::Archived === $this->getStatus();
    }

    public function isDeclined(): bool
    {
        return QuoteStatus::Declined === $this->getStatus();
    }
}
