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

namespace SolidInvoice\CoreBundle\Enum;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * What a line bills its quantity in, as a UN/ECE Recommendation 20 code.
 *
 * The codes are the standard's, not ours, because EN 16931 BT-130 accepts nothing else — so
 * keying the enum on them means the e-invoicing mapping is already done.
 *
 * Recommendation 20 runs to some 1,800 codes. These are the ones an invoice is actually
 * billed in; another one is a case here plus its `line.unit.*` translation. There is
 * deliberately no free-text column to hold the rest: an unrecognised code would only be
 * discovered when a tax authority rejected the e-invoice, which is too late to be useful.
 *
 * Case order is the order the line editor offers them in.
 */
enum UnitCode: string implements TranslatableInterface
{
    case UNIT = 'C62';
    case HOUR = 'HUR';
    case DAY = 'DAY';
    case MONTH = 'MON';
    case YEAR = 'ANN';
    case SERVICE = 'E48';
    case KILOGRAM = 'KGM';
    case TONNE = 'TNE';
    case METRE = 'MTR';
    case SQUARE_METRE = 'MTK';
    case CUBIC_METRE = 'MTQ';
    case LITRE = 'LTR';

    /**
     * How the unit reads in a list — the line editor's dropdown, which offers "hours" rather
     * than "Hour" because that is how it reads after a quantity.
     *
     * The plural form, which is what a list wants: the entry names the unit, not one of
     * anything. {@see forQuantity()} is the one that has to agree with a number.
     */
    #[Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->forQuantity($translator, 2, $locale);
    }

    /**
     * The unit as it reads after a given quantity — "1 hour", "12 hours", "40 kg".
     *
     * Takes the quantity, because an invoice billing a single hour has to say "1 hour" and
     * not "1 hours". The units written as symbols do not inflect, and their messages carry
     * no plural form for the translator to choose between.
     */
    public function forQuantity(TranslatorInterface $translator, float $quantity, ?string $locale = null): string
    {
        return $translator->trans('line.unit.' . $this->value, ['%count%' => $quantity], locale: $locale);
    }
}
