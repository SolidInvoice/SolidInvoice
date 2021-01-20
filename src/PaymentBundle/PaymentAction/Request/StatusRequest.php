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

namespace SolidInvoice\PaymentBundle\PaymentAction\Request;

use Payum\Core\Request\BaseGetStatus;
use SolidInvoice\PaymentBundle\Model\Status;

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
        return Status::STATUS_NEW === $this->status;
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
        return Status::STATUS_SUSPENDED === $this->status;
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
        return Status::STATUS_EXPIRED === $this->status;
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
        return Status::STATUS_CANCELLED === $this->status;
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
        return Status::STATUS_PENDING === $this->status;
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
        return Status::STATUS_FAILED === $this->status;
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
        return Status::STATUS_UNKNOWN === $this->status;
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
        return Status::STATUS_CAPTURED === $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return Status::STATUS_AUTHORIZED === $this->status;
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
        return Status::STATUS_REFUNDED === $this->status;
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

    public function isPayedout(): bool
    {
        return false;
    }
}
