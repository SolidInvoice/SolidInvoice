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

namespace SolidInvoice\EInvoiceBundle\Exception;

use RuntimeException;
use Symfony\Component\Uid\Ulid;

/**
 * Thrown by {@see \SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument}'s guarded setters and by
 * {@see \SolidInvoice\EInvoiceBundle\Listener\ImmutablePayloadListener} when a document whose
 * status is already terminal is written to. An audit record of what was transmitted must not
 * change after the fact — see `design` §6 on SOL-89.
 */
final class DocumentAlreadyClearedException extends RuntimeException implements EInvoiceExceptionInterface
{
    public function __construct(?Ulid $id)
    {
        parent::__construct(sprintf(
            'E-invoice document%s cannot be modified: its status is already terminal.',
            $id instanceof Ulid ? sprintf(' "%s"', $id->toString()) : '',
        ));
    }
}
