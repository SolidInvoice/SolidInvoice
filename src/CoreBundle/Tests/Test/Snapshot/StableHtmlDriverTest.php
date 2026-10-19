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
     *
     * The literal (not entity-encoded) UTF-8 character is the canonical form
     * here, not an arbitrary choice: saveHTML() itself picks between
     * `&oacute;` and `ó` depending on the libxml2 version doing the
     * serializing (confirmed on PR #3045 — identical input, 2.9.14 emits the
     * entity, the libxml2 shipped with Ubuntu 26.04 emits the literal
     * character), so asserting the entity form here would pin this test to
     * this host's libxml2 rather than catching the regression it exists for.
     */
    public function testSerializeIsStableAndEncodingCorrectForSingleQuotedJsonAttributeAndNonAsciiText(): void
    {
        $driver = new StableHtmlDriver();

        $html = '<div data-live-props-value=\'{"label":"Costa Rican Colón"}\'>Café</div>';

        $first = $driver->serialize($html);
        $second = $driver->serialize($html);

        self::assertSame($first, $second, 'Serializing the same input twice must produce byte-identical output.');

        self::assertStringContainsString(
            'data-live-props-value="{&quot;label&quot;:&quot;Costa Rican Colón&quot;}"',
            $first,
            'The attribute must always be double-quoted with its JSON content &quot;-escaped, regardless of host libxml2.',
        );

        self::assertStringContainsString('Café', $first, 'Non-ASCII text must be decoded as a literal UTF-8 character, not left as an entity or mangled into mojibake.');
        self::assertStringNotContainsString('&Atilde;', $first, 'A UTF-8 byte sequence misread as Latin-1 (mojibake) means the pre-parse ASCII-safe encoding was not applied.');
    }

    /**
     * Same fixture, fed in pre-entity-encoded instead of as a literal
     * character. Whichever form libxml2's serializer picks on this host, the
     * post-process decode step must still normalise it to the literal
     * character — this is what makes the driver host-independent rather than
     * merely reproducing one host's current behaviour.
     */
    public function testSerializeNormalisesAPreEncodedNonAsciiEntityToTheSameLiteralCharacter(): void
    {
        $driver = new StableHtmlDriver();

        $literal = $driver->serialize('<p>Café</p>');
        $preEncoded = $driver->serialize('<p>Caf&eacute;</p>');

        self::assertSame($literal, $preEncoded);
    }

    /**
     * Regression guard: the pre-parse ASCII-safe encoding step used to go
     * through mb_encode_numericentity(), whose convmap silently corrupts any
     * character outside the Basic Multilingual Plane. The onboarding email
     * snapshot's feature icons are emoji, which is exactly that case — this
     * fixture pins that an emoji survives serialization intact.
     */
    public function testSerializePreservesAnEmojiOutsideTheBasicMultilingualPlane(): void
    {
        $result = new StableHtmlDriver()->serialize('<p>👥</p>');

        self::assertStringContainsString('👥', $result);
    }

    public function testExtensionIsHtml(): void
    {
        self::assertSame('html', new StableHtmlDriver()->extension());
    }

    /**
     * Earlier versions of this driver forced UTF-8 decoding with a
     * `<?xml encoding="utf-8" ?>` processing instruction prepended to the
     * input, which some libxml2 releases serialize back into the output as a
     * literal `<!--?xml ... ?-->` comment — worse than the mojibake it was
     * meant to prevent. This driver no longer injects that hint at all (see
     * the class docblock), so there is nothing to leak; this test pins that
     * a leading `<!DOCTYPE ...>` (as the invoice/quote email templates have)
     * still comes out first and clean.
     */
    public function testSerializeDoesNotLeakAnyParserHintAfterALeadingDoctype(): void
    {
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
            . "\n<html xmlns=\"http://www.w3.org/1999/xhtml\"><body><p>café</p></body></html>";

        $result = new StableHtmlDriver()->serialize($html);

        self::assertStringNotContainsString('<?xml', $result);
        self::assertStringNotContainsString('encoding="utf-8"', $result);
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
