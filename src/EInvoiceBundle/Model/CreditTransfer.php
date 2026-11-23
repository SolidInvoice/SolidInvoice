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
 * Credit transfer instructions (BG-17).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\CreditTransferTest
 */
final readonly class CreditTransfer
{
    public function __construct(
        /**
         * BT-84. The account the payment should be made to.
         */
        public string $accountIdentifier,
        /**
         * BT-85. The name of the account the payment should be made to.
         */
        public ?string $accountName = null,
        /**
         * BT-86. An identifier for the payment service provider.
         */
        public ?string $serviceProviderIdentifier = null,
    ) {
    }
}
