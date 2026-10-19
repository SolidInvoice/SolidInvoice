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

use const LIBXML_HTML_NODEFDTD;
use const PHP_OS_FAMILY;
use DOMDocument;
use PHPUnit\Framework\Assert;
use Spatie\Snapshots\Driver;
use Spatie\Snapshots\Exceptions\CantBeSerialized;
use function is_string;
use function preg_replace;
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
 * This driver forces the input encoding with the `<?xml encoding="utf-8" ?>`
 * processing-instruction hint (consumed by the parser, never added as a node,
 * unlike a literal <meta charset> tag which would land in an auto-created <head>
 * and change the snapshot's markup) and then normalises attribute quoting itself
 * instead of trusting saveHTML()'s choice, so the output no longer depends on
 * which libxml2 the test happens to run against.
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

        $domDocument = new DOMDocument('1.0');
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;

        @$domDocument->loadHTML('<?xml encoding="utf-8" ?>' . $data, LIBXML_HTML_NODEFDTD);

        $html = (string) $domDocument->saveHTML();
        // When the source has its own <!DOCTYPE ...>, saveHTML() puts the doctype first and
        // leaves this hint as literal text straight after it instead of consuming it, so the
        // removal cannot be anchored to the start of the string.
        $html = (string) preg_replace('/<\?xml\s+encoding="utf-8"\s*\?>/', '', $html);
        $html = $this->withStableAttributeQuoting($html);

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
}
