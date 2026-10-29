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

namespace SolidInvoice\PaymentBundle\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use SolidInvoice\PaymentBundle\Enum\PaymentConflict;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Rendered by the REST API as a 409 problem whose `type` names the conflict.
 */
final class PaymentConflictException extends ConflictHttpException implements ProblemExceptionInterface
{
    public function __construct(
        public readonly PaymentConflict $conflict,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function getType(): string
    {
        return $this->conflict->type();
    }

    public function getTitle(): string
    {
        return $this->conflict->value;
    }

    public function getStatus(): int
    {
        return $this->getStatusCode();
    }

    public function getDetail(): string
    {
        return $this->getMessage();
    }

    public function getInstance(): ?string
    {
        return null;
    }
}
