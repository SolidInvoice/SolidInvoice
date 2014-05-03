<?php

namespace CSBill\PaymentBundle\Action\Request;

use CSBill\PaymentBundle\Entity\Status;
use Payum\Core\Request\BaseStatusRequest;

class StatusRequest extends BaseStatusRequest
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
    public function markSuccess()
    {
        $this->status = STATUS::STATUS_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccess()
    {
        return $this->status === STATUS::STATUS_SUCCESS;
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
} 