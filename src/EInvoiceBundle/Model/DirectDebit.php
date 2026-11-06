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

/**
 * Direct debit information (BG-19).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\DirectDebitTest
 */
final readonly class DirectDebit
{
    public function __construct(
        /**
         * BT-89. The mandate reference identifying the direct debit authorisation.
         */
        public ?string $mandateReference = null,
        /**
         * BT-90. The creditor's identifier for the direct debit.
         */
        public ?string $creditorIdentifier = null,
        /**
         * BT-91. The account the payment will be debited from.
         */
        public ?string $debitedAccountIdentifier = null,
    ) {
    }
}
