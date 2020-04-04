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

namespace SolidInvoice\QuoteBundle\Traits;

use SolidInvoice\QuoteBundle\Model\Graph;

trait QuoteStatusTrait
{
    abstract public function getStatus();

    public function isPending(): bool
    {
        return Graph::STATUS_PENDING === $this->getStatus();
    }

    public function isDraft(): bool
    {
        return Graph::STATUS_DRAFT === $this->getStatus();
    }

    public function isCancelled(): bool
    {
        return Graph::STATUS_CANCELLED === $this->getStatus();
    }

    public function isAccepted(): bool
    {
        return Graph::STATUS_ACCEPTED === $this->getStatus();
    }

    public function isArchived(): bool
    {
        return Graph::STATUS_ARCHIVED === $this->getStatus();
    }

    public function isDeclined(): bool
    {
        return Graph::STATUS_DECLINED === $this->getStatus();
    }
}
