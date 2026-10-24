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

namespace SolidInvoice\EInvoiceBundle\Model;

use DateTimeImmutable;

/**
 * A date range. Shared by BG-14 (invoice-level invoicing period) and BG-26 (invoice line period).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\InvoicingPeriodTest
 */
final readonly class InvoicingPeriod
{
    public function __construct(
        /**
         * BT-73 (invoice level) / BT-134 (line level).
         */
        public ?DateTimeImmutable $startDate = null,
        /**
         * BT-74 (invoice level) / BT-135 (line level).
         */
        public ?DateTimeImmutable $endDate = null,
    ) {
    }
}
