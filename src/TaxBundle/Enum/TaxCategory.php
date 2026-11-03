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

namespace SolidInvoice\TaxBundle\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TaxCategory: string implements TranslatableInterface
{
    case Standard = 'Standard';
    case ZeroRated = 'ZeroRated';
    case Exempt = 'Exempt';
    case OutOfScope = 'OutOfScope';
    case ReverseCharge = 'ReverseCharge';

    public function getLabel(): string
    {
        return match ($this) {
            self::Standard => 'tax.category.standard',
            self::ZeroRated => 'tax.category.zero_rated',
            self::Exempt => 'tax.category.exempt',
            self::OutOfScope => 'tax.category.out_of_scope',
            self::ReverseCharge => 'tax.category.reverse_charge',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->getLabel(), [], null, $locale);
    }
}
