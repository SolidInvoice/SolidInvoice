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

namespace SolidInvoice\CoreBundle\Test\Snapshot;

use const ENT_HTML5;
use const ENT_QUOTES;
use const LIBXML_HTML_NODEFDTD;
use const PHP_OS_FAMILY;
use DOMDocument;
use PHPUnit\Framework\Assert;
use Spatie\Snapshots\Driver;
use Spatie\Snapshots\Exceptions\CantBeSerialized;
use function html_entity_decode;
use function is_string;
use function mb_ord;
use function mb_strlen;
use function preg_replace_callback;
use function str_replace;

/**
 * Replacement for spatie/phpunit-snapshot-assertions' HtmlDriver.
 *
 * The upstream driver loads HTML into a DOMDocument with no charset hint and then
 * calls DOMDocument::saveHTML() directly. Both steps depend on the host's libxml2:
 * an un-hinted load mangles non-ASCII text, and saveHTML()'s attribute-quoting
 * choice (single-quoted literal vs double-quoted &quot;-escaped) changed between
 * libxml2 releases. That is what reddened 92 snapshot tests when GitHub moved
 * `ubuntu-latest` from 24.04 to 26.04 with no code change on our side (SOL-518).
 *
 * This driver has two independent steps, neither of which trusts libxml2's own
 * choices:
 *
 * 1. Before parsing, every non-ASCII character is pre-converted to a numeric
 *    character reference one codepoint at a time with {@see mb_ord()}, so the
 *    string DOMDocument::loadHTML() actually sees is pure ASCII. That removes
 *    any chance of the parser misreading a UTF-8 byte sequence as Latin-1
 *    (the mojibake, e.g. `&Atilde;&sup3;`, that an un-hinted load produces),
 *    regardless of which libxml2 does the parsing. This has to walk codepoints
 *    by hand rather than use {@see mb_encode_numericentity()}: that function's
 *    convmap maps a character outside the Basic Multilingual Plane (an emoji,
 *    for example) to the wrong number and silently corrupts it.
 * 2. After DOMDocument::saveHTML(), every HTML character reference that
 *    decodes to a non-ASCII character is decoded back to the literal UTF-8
 *    character. This step exists because saveHTML() itself is not
 *    deterministic here: parsing always turns a reference into a plain
 *    Unicode text node internally, so which form (`&oacute;` vs the literal
 *    `ó`) the *serializer* then chooses is a second, independent libxml2
 *    version difference, left open even once parsing is made safe by step 1.
 *    A `<?xml encoding="utf-8" ?>` content hint does not avoid this: it only
 *    helps parsing, and on newer libxml2 the hint itself is serialized back
 *    into the output as a literal `<!--?xml encoding="utf-8" ?-->` comment,
 *    which is worse. Decoding is restricted to non-ASCII results so that
 *    structural references (`&amp;`, `&lt;`, `&gt;`, `&quot;`, `&#39;`) are
 *    left untouched.
 *
 * Attribute quoting is then normalised separately, so the result no longer
 * depends on which libxml2 the test happens to run against.
 * @see \SolidInvoice\CoreBundle\Tests\Test\Snapshot\StableHtmlDriverTest
 */
final class StableHtmlDriver implements Driver
{
    public function serialize(mixed $data): string
    {
        if (! is_string($data)) {
            throw new CantBeSerialized('Only strings can be serialized to html');
        }

        if ($data === '') {
            return "\n";
        }

        $asciiSafeData = (string) preg_replace_callback(
            '/[\x{80}-\x{10FFFF}]/u',
            static fn (array $characterMatch): string => '&#' . mb_ord($characterMatch[0], 'UTF-8') . ';',
            $data,
        );

        $domDocument = new DOMDocument('1.0');
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;

        @$domDocument->loadHTML($asciiSafeData, LIBXML_HTML_NODEFDTD);

        $html = (string) $domDocument->saveHTML();
        $html = $this->withStableAttributeQuoting($html);
        $html = $this->withLiteralNonAsciiCharacters($html);

        if (PHP_OS_FAMILY === 'Windows') {
            return str_replace("\r\n", "\n", $html);
        }

        return $html;
    }

    public function extension(): string
    {
        return 'html';
    }

    public function match(mixed $expected, mixed $actual): void
    {
        Assert::assertEquals($expected, $this->serialize($actual));
    }

    /**
     * Rewrite every attribute saveHTML() produced into the double-quoted,
     * &quot;-escaped form, so the result is the same whether the host libxml2
     * chose single or double quotes.
     *
     * This has to parse each start tag's attributes rather than pattern-match
     * `name='value'` over the whole document: an attribute value can itself
     * contain a quoted substring (`content="0;url='/payments/methods'"`), and
     * a document-wide pattern cannot tell that occurrence apart from a real
     * single-quoted attribute boundary.
     */
    private function withStableAttributeQuoting(string $html): string
    {
        return (string) preg_replace_callback(
            '/<([a-zA-Z][a-zA-Z0-9]*)((?:\s+[^<>]*)?)(\/?)>/',
            function (array $tagMatch): string {
                [, $tagName, $attributeRegion, $selfClosing] = $tagMatch;

                return '<' . $tagName . $this->withStableAttributes($attributeRegion) . $selfClosing . '>';
            },
            $html,
        );
    }

    private function withStableAttributes(string $attributeRegion): string
    {
        preg_match_all(
            '/([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\s*=\s*("[^"]*"|\'[^\']*\'))?/',
            $attributeRegion,
            $attributeMatches,
            PREG_SET_ORDER,
        );

        $attributes = '';

        foreach ($attributeMatches as $attributeMatch) {
            $name = $attributeMatch[1];
            $rawValue = $attributeMatch[2] ?? null;

            if ($rawValue === null) {
                $attributes .= ' ' . $name;

                continue;
            }

            $value = substr($rawValue, 1, -1);

            if ($rawValue[0] === "'") {
                $value = str_replace('"', '&quot;', $value);
            }

            $attributes .= ' ' . $name . '="' . $value . '"';
        }

        return $attributes;
    }

    /**
     * Decode every HTML character reference that represents a non-ASCII
     * character back to that literal UTF-8 character, so the snapshot does
     * not depend on whether this host's libxml2 chose to emit `&oacute;` or
     * `ó`. References that decode to ASCII (`&amp;`, `&lt;`, `&gt;`,
     * `&quot;`, `&#39;`) are left alone, since those are structural, not
     * text content.
     */
    private function withLiteralNonAsciiCharacters(string $html): string
    {
        return (string) preg_replace_callback(
            '/&(?:#x[0-9a-fA-F]+|#[0-9]+|[a-zA-Z][a-zA-Z0-9]*);/',
            static function (array $referenceMatch): string {
                $decoded = html_entity_decode($referenceMatch[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (mb_strlen($decoded) !== 1 || mb_ord($decoded) < 128) {
                    return $referenceMatch[0];
                }

                return $decoded;
            },
            $html,
        );
    }
}
