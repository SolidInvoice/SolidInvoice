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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Case;

use RuntimeException;
use function sprintf;

/**
 * The validation profile a conformance case must run under. The first five values are the
 * VEFA <testSet configuration="..."> values measured across the en16931 and peppol-bis-3
 * corpora; XRechnung has no VEFA testSet corpus of its own and is only ever assigned to
 * whole-document KoSIT instance cases.
 */
enum Profile: string
{
    case Tc434Ubl = 'tc434-ubl';

    case Tc434Cii = 'tc434-cii';

    case PeppolBisBase30Ubl = 'peppolbis-en16931-base-3.0-ubl';

    case PeppolBisBase30Cii = 'peppolbis-en16931-base-3.0-cii';

    case PeppolBisInvoice01Ubl = 'peppolbis-en16931-01-3.0-ubl-invoice';

    case XRechnung = 'xrechnung';

    /**
     * Maps a VEFA testSet "configuration" attribute. An unrecognised value is a parser
     * failure, never a silent skip: it means the corpus introduced a configuration this
     * harness does not know about yet.
     */
    public static function fromConfiguration(string $configuration): self
    {
        return match ($configuration) {
            'tc434-ubl' => self::Tc434Ubl,
            'tc434-cii' => self::Tc434Cii,
            'peppolbis-en16931-base-3.0-ubl' => self::PeppolBisBase30Ubl,
            'peppolbis-en16931-base-3.0-cii' => self::PeppolBisBase30Cii,
            'peppolbis-en16931-01-3.0-ubl-invoice' => self::PeppolBisInvoice01Ubl,
            default => throw new RuntimeException(sprintf('"%s" is not a known VEFA testSet configuration.', $configuration)),
        };
    }
}
