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

enum TaxDirection: string
{
    case Additive = 'Additive';
    case Deductive = 'Deductive';
    case Informational = 'Informational';

    /**
     * Human-friendly display label. Left untranslated so non-display consumers (API,
     * exports, logs) get stable English; translation happens at the render chokepoint
     * via {@see self::translationKey()} (see the InvoiceTaxType choice_label).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Additive => 'Additive',
            self::Deductive => 'Deductive',
            self::Informational => 'Informational',
        };
    }

    /**
     * Translation key used when the direction is rendered to the user.
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Additive => 'tax.direction.additive',
            self::Deductive => 'tax.direction.deductive',
            self::Informational => 'tax.direction.informational',
        };
    }
}
