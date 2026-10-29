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

namespace SolidInvoice\ApiBundle\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class RecordPaymentInput
{
    /**
     * Major units of the invoice currency, as a decimal string or number ("150.00", 150.5). A JSON
     * integer is the deprecated form and is read as minor units.
     */
    #[Assert\NotNull]
    public string | int | float | null $amount = null;

    /**
     * Must be the invoice currency when given.
     */
    #[Assert\Currency]
    public ?string $currency = null;

    /**
     * Gateway name of an enabled offline or internal payment method; `cash` when omitted.
     */
    public ?string $method = null;

    /**
     * The date the payment was received; today when omitted.
     */
    #[Assert\Date]
    public ?string $paidAt = null;

    #[Assert\Length(max: 255)]
    public ?string $reference = null;

    public ?string $notes = null;
}
