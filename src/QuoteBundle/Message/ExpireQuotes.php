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

namespace SolidInvoice\QuoteBundle\Message;

use Symfony\Component\Uid\Ulid;

/**
 * Expire every pending quote of one company whose validity date has passed.
 */
final readonly class ExpireQuotes
{
    public function __construct(
        public Ulid $companyId,
    ) {
    }
}
