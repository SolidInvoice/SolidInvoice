<?php
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Traits;

use CSBill\QuoteBundle\Model\Graph;

trait QuoteStatusTrait
{
    abstract public function getStatus();

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
    public function isDraft()
    {
        return Graph::STATUS_DRAFT === $this->getStatus();
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
    public function isAccepted()
    {
        return Graph::STATUS_ACCEPTED === $this->getStatus();
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
    public function isDeclined()
    {
        return Graph::STATUS_DECLINED === $this->getStatus();
    }
}