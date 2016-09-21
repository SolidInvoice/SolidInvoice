<?php
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Traits;

use CSBill\InvoiceBundle\Model\Graph;

trait InvoiceStatusTrait
{
    abstract public function getStatus();

    /**
     * @return bool
     */
    public function isPaid()
    {
        return Graph::STATUS_PAID === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isArchived()
    {
        return Graph::STATUS_ARCHIVED === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isCancelled()
    {
        return Graph::STATUS_CANCELLED === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return Graph::STATUS_DRAFT === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return Graph::STATUS_NEW === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isOverdue()
    {
        return Graph::STATUS_OVERDUE === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return Graph::STATUS_PENDING === $this->getStatus();
    }

    /**
     * @return bool
     */
    public function isRecurring()
    {
        return Graph::STATUS_RECURRING === $this->getStatus();
    }
}
