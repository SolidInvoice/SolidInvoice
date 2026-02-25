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
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;

class StatusRequest extends BaseGetStatus
{
    public function markNew(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::New->value;
    }

    public function isNew(): bool
    {
        return PaymentStatus::New->value === $this->status;
    }

    public function markSuspended(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Suspended->value;
    }

    public function isSuspended(): bool
    {
        return PaymentStatus::Suspended->value === $this->status;
    }

    public function markExpired(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Expired->value;
    }

    public function isExpired(): bool
    {
        return PaymentStatus::Expired->value === $this->status;
    }

    public function markCanceled(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Cancelled->value;
    }

    public function isCanceled(): bool
    {
        return PaymentStatus::Cancelled->value === $this->status;
    }

    public function markPending(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Pending->value;
    }

    public function isPending(): bool
    {
        return PaymentStatus::Pending->value === $this->status;
    }

    public function markFailed(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Failed->value;
    }

    public function isFailed(): bool
    {
        return PaymentStatus::Failed->value === $this->status;
    }

    public function markUnknown(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Unknown->value;
    }

    public function isUnknown(): bool
    {
        return PaymentStatus::Unknown->value === $this->status;
    }

    public function markCaptured(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Captured->value;
    }

    public function isCaptured(): bool
    {
        return PaymentStatus::Captured->value === $this->status;
    }

    public function isAuthorized(): bool
    {
        return PaymentStatus::Authorized->value === $this->status;
    }

    public function markAuthorized(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Authorized->value;
    }

    public function isRefunded(): bool
    {
        return PaymentStatus::Refunded->value === $this->status;
    }

    public function markRefunded(): void
    {
        /** @phpstan-ignore-next-line Payum library compatibility */
        $this->status = PaymentStatus::Refunded->value;
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
