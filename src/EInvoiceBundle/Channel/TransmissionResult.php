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

namespace SolidInvoice\EInvoiceBundle\Channel;

use DateTimeImmutable;
use SolidInvoice\EInvoiceBundle\Enum\TransmissionState;

/**
 * @see \SolidInvoice\EInvoiceBundle\Tests\Channel\TransmissionResultTest
 */
final readonly class TransmissionResult
{
    /**
     * @param list<TransmissionMessage> $messages
     */
    public function __construct(
        public TransmissionState $state,
        public ?string $externalId = null,
        public ?string $receipt = null,
        public array $messages = [],
        public ?DateTimeImmutable $occurredAt = null,
        public ?DateTimeImmutable $pollAfter = null,
    ) {
    }
}
