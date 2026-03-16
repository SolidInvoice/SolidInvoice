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
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    public int $amount = 0;

    #[Assert\NotBlank]
    #[Assert\Currency]
    public string $currency = '';

    public ?string $reference = null;

    public ?string $notes = null;
}
