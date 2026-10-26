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

enum TaxType: string implements TranslatableInterface
{
    case Inclusive = 'Inclusive';
    case Exclusive = 'Exclusive';
    case FlatRate = 'Flat Rate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Inclusive => 'tax.inclusive',
            self::Exclusive => 'tax.exclusive',
            self::FlatRate => 'tax.flat_rate',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->getLabel(), [], null, $locale);
    }
}
