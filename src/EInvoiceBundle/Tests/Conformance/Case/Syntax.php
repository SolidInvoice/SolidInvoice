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

use DOMDocument;
use DOMElement;
use RuntimeException;
use function sprintf;

/**
 * The XML syntax a whole-document conformance case is written in. Fragment cases (RuleCase)
 * do not need this: their syntax is already implied by the VEFA testSet configuration.
 */
enum Syntax: string
{
    case Ubl = 'ubl';

    case Cii = 'cii';

    /**
     * Reads the root element of a whole document to tell UBL and CII apart.
     */
    public static function detect(string $document): self
    {
        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();
            $loaded = $dom->loadXML($document, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }

        if (! $loaded) {
            throw new RuntimeException('Document is not well-formed XML.');
        }

        $root = $dom->documentElement;

        if (! $root instanceof DOMElement) {
            throw new RuntimeException('Document has no root element.');
        }

        return match ($root->localName) {
            'CrossIndustryInvoice' => self::Cii,
            'Invoice', 'CreditNote', 'Order' => self::Ubl,
            default => throw new RuntimeException(sprintf('"%s" is not a recognised document root.', $root->localName)),
        };
    }
}
