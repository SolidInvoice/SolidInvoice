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

use SolidInvoice\EInvoiceBundle\Enum\PaymentMeansCode;

/**
 * Payment instructions (BG-16).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\PaymentInstructionsTest
 */
final readonly class PaymentInstructions
{
    public function __construct(
        /**
         * BT-81. The means by which payment is expected to be made.
         */
        public PaymentMeansCode $paymentMeansCode,
        /**
         * BT-82. A textual description of the payment means, for a code not covered by BT-81.
         */
        public ?string $paymentMeansText = null,
        /**
         * BT-83. Remittance information for the buyer to quote when paying.
         */
        public ?string $remittanceInformation = null,
        /**
         * BG-17 (BT-84…BT-86 via CreditTransfer). Credit transfer instructions.
         *
         * @var list<CreditTransfer>
         */
        public array $creditTransfers = [],
        /**
         * BG-18 (BT-87/BT-88 via CardInformation). Payment card information.
         */
        public ?CardInformation $card = null,
        /**
         * BG-19 (BT-89…BT-91 via DirectDebit). Direct debit information.
         */
        public ?DirectDebit $directDebit = null,
    ) {
    }
}
