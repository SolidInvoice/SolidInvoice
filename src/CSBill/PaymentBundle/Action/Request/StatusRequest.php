<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action\Request;

use CSBill\PaymentBundle\Entity\Status;
use Payum\Core\Request\BaseGetStatus;

class StatusRequest extends BaseGetStatus
{
    /**
     * {@inheritdoc}
     */
    public function markNew()
    {
        $this->status = Status::STATUS_NEW;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew()
    {
        return $this->status === Status::STATUS_NEW;
    }

    /**
     * {@inheritdoc}
     */
    public function markSuspended()
    {
        $this->status = STATUS::STATUS_SUSPENDED;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuspended()
    {
        return $this->status === STATUS::STATUS_SUSPENDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markExpired()
    {
        $this->status = STATUS::STATUS_EXPIRED;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        return $this->status === STATUS::STATUS_EXPIRED;
    }

    /**
     * {@inheritdoc}
     */
    public function markCanceled()
    {
        $this->status = STATUS::STATUS_CANCELED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCanceled()
    {
        return $this->status === STATUS::STATUS_CANCELED;
    }

    /**
     * {@inheritdoc}
     */
    public function markPending()
    {
        $this->status = STATUS::STATUS_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function isPending()
    {
        return $this->status === STATUS::STATUS_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function markFailed()
    {
        $this->status = STATUS::STATUS_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return $this->status === STATUS::STATUS_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function markUnknown()
    {
        $this->status = STATUS::STATUS_UNKNOWN;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnknown()
    {
        return $this->status === STATUS::STATUS_UNKNOWN;
    }

    /**
     * {@inheritdoc}
     */
    public function markCaptured()
    {
        $this->status = STATUS::STATUS_CAPTURED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCaptured()
    {
        return $this->status === STATUS::STATUS_CAPTURED;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return $this->status === STATUS::STATUS_AUTHORIZED;
    }

    /**
     * {@inheritdoc}
     */
    public function markAuthorized()
    {
        $this->status = STATUS::STATUS_AUTHORIZED;
    }

    /**
     * {@inheritdoc}
     */
    public function isRefunded()
    {
        return $this->status === STATUS::STATUS_REFUNDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markRefunded()
    {
        $this->status = STATUS::STATUS_REFUNDED;
    }
}
