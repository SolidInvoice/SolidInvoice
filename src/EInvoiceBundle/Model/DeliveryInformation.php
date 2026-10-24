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
 * Delivery information (BG-13).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\DeliveryInformationTest
 */
final readonly class DeliveryInformation
{
    public function __construct(
        /**
         * BT-70. The name of the party goods are delivered to, where it differs from the buyer.
         */
        public ?string $deliverToName = null,
        /**
         * BT-71/71-1. An identifier for the delivery location.
         */
        public ?Identifier $locationIdentifier = null,
        /**
         * BT-72. The date goods or services were actually delivered.
         */
        public ?DateTimeImmutable $actualDeliveryDate = null,
        /**
         * BG-14 (BT-73/BT-74 via InvoicingPeriod). The invoicing period this delivery falls within.
         */
        public ?InvoicingPeriod $invoicingPeriod = null,
        /**
         * BG-15 (BT-75…BT-80 via PostalAddress). The delivery address.
         */
        public ?PostalAddress $address = null,
    ) {
    }
}
