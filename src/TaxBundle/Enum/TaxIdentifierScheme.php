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

enum TaxIdentifierScheme: string
{
    // BT-31 / BT-48 — PartyTaxScheme, TaxScheme ID = VAT
    case Vat = 'vat';

    // BT-32 — PartyTaxScheme, non-VAT scheme
    case TaxRegistration = 'tax_registration';

    // BT-63 — tax representative party
    case TaxRepresentativeVat = 'tax_representative_vat';

    // BT-47 / BT-30 — PartyLegalEntity/CompanyID
    case LegalRegistration = 'legal_registration';

    /**
     * Translation key used when the scheme is rendered to the user.
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Vat => 'tax.identifier_scheme.vat',
            self::TaxRegistration => 'tax.identifier_scheme.tax_registration',
            self::TaxRepresentativeVat => 'tax.identifier_scheme.tax_representative_vat',
            self::LegalRegistration => 'tax.identifier_scheme.legal_registration',
        };
    }
}
