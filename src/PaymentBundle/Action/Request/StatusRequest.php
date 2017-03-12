<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action\Request;

use CSBill\PaymentBundle\Model\Status;
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
        $this->status = Status::STATUS_SUSPENDED;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuspended()
    {
        return $this->status === Status::STATUS_SUSPENDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markExpired()
    {
        $this->status = Status::STATUS_EXPIRED;
    }

    /**
     * {@inheritdoc}
     */
    public function isExpired()
    {
        return $this->status === Status::STATUS_EXPIRED;
    }

    /**
     * {@inheritdoc}
     */
    public function markCanceled()
    {
        $this->status = Status::STATUS_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCanceled()
    {
        return $this->status === Status::STATUS_CANCELLED;
    }

    /**
     * {@inheritdoc}
     */
    public function markPending()
    {
        $this->status = Status::STATUS_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function isPending()
    {
        return $this->status === Status::STATUS_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function markFailed()
    {
        $this->status = Status::STATUS_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return $this->status === Status::STATUS_FAILED;
    }

    /**
     * {@inheritdoc}
     */
    public function markUnknown()
    {
        $this->status = Status::STATUS_UNKNOWN;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnknown()
    {
        return $this->status === Status::STATUS_UNKNOWN;
    }

    /**
     * {@inheritdoc}
     */
    public function markCaptured()
    {
        $this->status = Status::STATUS_CAPTURED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCaptured()
    {
        return $this->status === Status::STATUS_CAPTURED;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return $this->status === Status::STATUS_AUTHORIZED;
    }

    /**
     * {@inheritdoc}
     */
    public function markAuthorized()
    {
        $this->status = Status::STATUS_AUTHORIZED;
    }

    /**
     * {@inheritdoc}
     */
    public function isRefunded()
    {
        return $this->status === Status::STATUS_REFUNDED;
    }

    /**
     * {@inheritdoc}
     */
    public function markRefunded()
    {
        $this->status = Status::STATUS_REFUNDED;
    }

    public function markPayedout()
    {
        // noop
    }

    /**
     * @return bool
     */
    public function isPayedout()
    {
        return false;
    }
}
