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
    public function markNew(): void
    {
        $this->status = Status::STATUS_NEW;
    }

    public function isNew()
    {
        return Status::STATUS_NEW === $this->status;
    }

    public function markSuspended(): void
    {
        $this->status = Status::STATUS_SUSPENDED;
    }

    public function isSuspended()
    {
        return Status::STATUS_SUSPENDED === $this->status;
    }

    public function markExpired(): void
    {
        $this->status = Status::STATUS_EXPIRED;
    }

    public function isExpired()
    {
        return Status::STATUS_EXPIRED === $this->status;
    }

    public function markCanceled(): void
    {
        $this->status = Status::STATUS_CANCELLED;
    }

    public function isCanceled()
    {
        return Status::STATUS_CANCELLED === $this->status;
    }

    public function markPending(): void
    {
        $this->status = Status::STATUS_PENDING;
    }

    public function isPending()
    {
        return Status::STATUS_PENDING === $this->status;
    }

    public function markFailed(): void
    {
        $this->status = Status::STATUS_FAILED;
    }

    public function isFailed()
    {
        return Status::STATUS_FAILED === $this->status;
    }

    public function markUnknown(): void
    {
        $this->status = Status::STATUS_UNKNOWN;
    }

    public function isUnknown()
    {
        return Status::STATUS_UNKNOWN === $this->status;
    }

    public function markCaptured(): void
    {
        $this->status = Status::STATUS_CAPTURED;
    }

    public function isCaptured()
    {
        return Status::STATUS_CAPTURED === $this->status;
    }

    public function isAuthorized()
    {
        return Status::STATUS_AUTHORIZED === $this->status;
    }

    public function markAuthorized(): void
    {
        $this->status = Status::STATUS_AUTHORIZED;
    }

    public function isRefunded()
    {
        return Status::STATUS_REFUNDED === $this->status;
    }

    public function markRefunded(): void
    {
        $this->status = Status::STATUS_REFUNDED;
    }

    public function markPayedout(): void
    {
        // noop
    }

    public function isPayedout(): bool
    {
        return false;
    }
}
