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

namespace SolidInvoice\CoreBundle\Tests\Test\Snapshot;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Test\Snapshot\StableHtmlDriver;

final class StableHtmlDriverTest extends TestCase
{
    /**
     * Regression guard for SOL-518/SOL-520. On a libxml2 that has no charset
     * hint, non-ASCII text gets mangled, and on some libxml2 releases a
     * single-quoted attribute value is reproduced with its literal, unescaped
     * quotes instead of being normalised to the double-quoted, &quot;-escaped
     * form. This fixture carries both at once, so a regression in either half
     * fails this test.
     */
    public function testSerializeIsStableAndEncodingCorrectForSingleQuotedJsonAttributeAndNonAsciiText(): void
    {
        $driver = new StableHtmlDriver();

        $html = '<div data-live-props-value=\'{"label":"Costa Rican Colón"}\'>Café</div>';

        $first = $driver->serialize($html);
        $second = $driver->serialize($html);

        self::assertSame($first, $second, 'Serializing the same input twice must produce byte-identical output.');

        self::assertStringContainsString(
            'data-live-props-value="{&quot;label&quot;:&quot;Costa Rican Col&oacute;n&quot;}"',
            $first,
            'The attribute must always be double-quoted with its JSON content &quot;-escaped, regardless of host libxml2.',
        );

        self::assertStringContainsString('Caf&eacute;', $first, 'Non-ASCII text must be decoded as UTF-8, not mangled into mojibake.');
        self::assertStringNotContainsString('&Atilde;', $first, 'A UTF-8 byte sequence misread as Latin-1 (mojibake) means the charset hint was not applied.');
    }

    public function testExtensionIsHtml(): void
    {
        self::assertSame('html', new StableHtmlDriver()->extension());
    }

    /**
     * The charset hint used to force UTF-8 decoding is itself a processing
     * instruction prepended to the input. When the input already starts with
     * its own `<!DOCTYPE ...>` (as the invoice/quote email templates do),
     * saveHTML() emits the doctype first and the hint right after it instead
     * of consuming it — the hint must still be stripped from there, not just
     * from the very start of the string.
     */
    public function testSerializeStripsTheCharsetHintAfterALeadingDoctype(): void
    {
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
            . "\n<html xmlns=\"http://www.w3.org/1999/xhtml\"><body><p>café</p></body></html>";

        $result = new StableHtmlDriver()->serialize($html);

        self::assertStringNotContainsString('<?xml encoding="utf-8" ?>', $result);
        self::assertStringStartsWith('<!DOCTYPE html PUBLIC', $result);
    }

    /**
     * A double-quoted attribute's value can itself contain a single-quoted
     * substring, as the meta-refresh redirect template does
     * (`content="0;url='/payments/methods'"`). Normalising quoting must not
     * mistake that embedded text for a second, single-quoted attribute.
     */
    public function testSerializeDoesNotTreatAQuotedSubstringInsideAnAttributeValueAsAnotherAttribute(): void
    {
        $html = '<meta http-equiv="refresh" content="0;url=\'/payments/methods\'">';

        $result = new StableHtmlDriver()->serialize($html);

        self::assertStringContainsString('content="0;url=\'/payments/methods\'"', $result);
    }
}
