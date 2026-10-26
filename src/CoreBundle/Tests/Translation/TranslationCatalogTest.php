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

namespace SolidInvoice\CoreBundle\Tests\Translation;

use const PREG_OFFSET_CAPTURE;
use const SORT_STRING;
use const T_COMMENT;
use const T_DOC_COMMENT;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function basename;
use function explode;
use function in_array;
use function is_array;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_replace_callback;
use function sort;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use function substr_count;
use function token_get_all;
use function trim;

/**
 * Lints the translation catalogs and the code that uses them, so the rules in TRANSLATING.md
 * are enforced by CI instead of by review:
 *
 *  - catalogs are nested, sorted, and use lowercase snake_case dotted keys;
 *  - plurals use ICU (`messages+intl-icu`), not the legacy `{1}…|]1,Inf[` syntax;
 *  - every literal key the code translates exists, and no natural-language text is used as a key;
 *  - templates carry no hard-coded user-facing text;
 *  - every catalog key is still referenced somewhere.
 */
final class TranslationCatalogTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../../../..';

    private const string KEY_PATTERN = '/^[a-z0-9_]+(\.[a-z0-9_]+)+$/';

    /**
     * Keys built at runtime (`'status.' ~ value`, `'payment.gateway.%s.%s'`, …). Keys under these
     * prefixes are not reported as unused, since their full id never appears in the source.
     *
     * @var array<string, list<string>>
     */
    private const array DYNAMIC_PREFIXES = [
        'messages' => [
            'installation.flow.',
            'installation.telemetry.',
            'invoice.recurring.days.',
            'invoice.recurring.months.',
            'invoice.recurring.schedule_summary.',
            'invoice.transition.action.',
            'invoice.transition.exception.',
            'invoice.transition.recurring.action.',
            'label.',
            'onboarding.company.fields.heard_about.choices.',
            'payment.gateway.',
            'payment_terms.',
            'quote.transition.action.',
            'quote.transition.exception.',
            'status.',
        ],
        'email' => [
            'invoice.reminder.subject.',
            'onboarding.',
        ],
    ];

    /**
     * Text that may legitimately appear untranslated in a template: product and brand names,
     * mPDF page tokens, sample data and query syntax. Keyed by template path suffix ('*' = any).
     *
     * @var array<string, list<string>>
     */
    private const array TEMPLATE_TEXT_ALLOWLIST = [
        '*' => ['/^&#xA0;$/', '/^\{PAGENO\}\s+\{nb\}$/', '/^USD$/', '/^(II|III|IV)\.$/'],
        'Layout/login.html.twig' => ['/^unsplash-logo$/', '/^Julian Zett$/'],
        'Security/_photo_credit.html.twig' => ['/^unsplash-logo$/', '/^Julian Zett$/'],
        'Email/Onboarding/_layout.html.twig' => ['/^Solid\s+Invoice$/'],
        'Email/Onboarding/send_first_invoice.html.twig' => ['/^#INV-0001$/'],
        'Email/Onboarding/turn_invoices_into_payments.html.twig' => ['/^(stripe|PayPal|ACH|SEPA)$/'],
        'Components/Settings.html.twig' => ['/^CNAME$/'],
        'Components/CreateRecurringInvoice.html.twig' => ['/^\{(day|day_name|month|year)\}$/'],
        // Search query syntax: qualifiers, their values and example queries are literal input.
        'Menu/search.html.twig' => ['/^Ctrl\s+K$/', '/^(in|status|amount|client|created|sort):/', '/^[\w ]+( · [\w ]+)+$/'],
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function catalogProvider(): iterable
    {
        foreach (new Finder()->files()->in(self::ROOT . '/translations')->name('*.en.yml') as $file) {
            yield $file->getFilename() => [$file->getPathname()];
        }
    }

    #[DataProvider('catalogProvider')]
    public function testCatalogIsSortedWithValidKeys(string $path): void
    {
        $errors = [];
        $check = static function (array $tree, string $prefix) use (&$check, &$errors): void {
            $keys = array_map(strval(...), array_keys($tree));
            $sorted = $keys;
            sort($sorted, SORT_STRING);

            if ($sorted !== $keys) {
                $errors[] = sprintf('keys under "%s" are not sorted alphabetically', $prefix === '' ? '(root)' : $prefix);
            }

            foreach ($tree as $key => $value) {
                $id = $prefix === '' ? (string) $key : $prefix . '.' . $key;

                if (is_array($value)) {
                    $check($value, $id);
                } elseif (! preg_match(self::KEY_PATTERN, $id)) {
                    $errors[] = sprintf('"%s" is not a lowercase snake_case dotted key', $id);
                }
            }
        };
        $check(Yaml::parseFile($path) ?? [], '');

        self::assertSame([], $errors, basename($path));
    }

    #[DataProvider('catalogProvider')]
    public function testCatalogUsesIcuForPlurals(string $path): void
    {
        if (str_contains($path, '+intl-icu')) {
            self::addToAssertionCount(1);

            return;
        }

        $legacy = array_keys(array_filter(
            $this->flatten(Yaml::parseFile($path) ?? []),
            static fn (string $message): bool => (bool) preg_match('/(^|\|)\s*(\{\d+\}|[\[\]]-?(\d+|Inf),\s*(\d+|Inf)[\[\]])/', $message),
        ));

        self::assertSame([], $legacy, sprintf('%s: plural messages must use ICU syntax in the messages+intl-icu catalog', basename($path)));
    }

    public function testTranslatedKeysExist(): void
    {
        $known = $this->allKeys();
        $errors = [];

        foreach ($this->sourceFiles(['*.twig']) as $file) {
            preg_match_all('/(?<![\w.~])([\'"])([^\'"\n]+?)\1\s*\|\s*trans\b/', $file->getContents(), $matches);

            // Everything piped to |trans in a template is meant to be a key.
            foreach ($matches[2] as $id) {
                $this->checkId($id, $known, $file, $errors, true);
            }
        }

        $phpPatterns = [
            '/addFlash\(\s*[^,]+,\s*\'([^\']*)\'/',
            '/new\s+TranslatableMessage\(\s*\'([^\']*)\'/',
            '/(?<![\w>$])t\(\s*\'([^\']*)\'/',
            '/->trans\(\s*\'([^\']*)\'/',
            '/->label\(\s*\'([^\']*)\'/',
            '/buildViolation\(\s*\'([^\']*)\'/',
            '/\'(?:label|help|placeholder|empty_label|invalid_message)\'\s*=>\s*\'([^\']*)\'/',
        ];

        foreach ($this->sourceFiles(['*.php']) as $file) {
            if (str_contains($file->getPathname(), '/Command/') || str_contains($file->getPathname(), '/Mcp')) {
                continue;
            }

            $code = $this->withoutComments($file->getContents());

            foreach ($phpPatterns as $pattern) {
                preg_match_all($pattern, $code, $matches);

                foreach ($matches[1] as $id) {
                    $this->checkId($id, $known, $file, $errors, false);
                }
            }
        }

        self::assertSame([], array_values(array_unique($errors)));
    }

    public function testTemplatesHaveNoHardCodedText(): void
    {
        $errors = [];

        foreach ($this->sourceFiles(['*.html.twig', '*.txt.twig']) as $file) {
            $path = $file->getPathname();

            if (str_contains($path, '/NotificationBundle/Resources/views/dev/')) {
                continue; // code generator templates
            }

            foreach ($this->hardCodedText($file->getContents()) as [$line, $text]) {
                if (! $this->isAllowed($path, $text)) {
                    $errors[] = sprintf('%s:%d "%s"', $this->relative($path), $line, $text);
                }
            }
        }

        self::assertSame([], $errors, 'Move this text into translations/*.en.yml and render it with |trans');
    }

    public function testTextEmailsUseTheTxtExtension(): void
    {
        // Twig picks the escaping strategy from the extension: *.txt.twig is not HTML-escaped,
        // anything else is, which turns "don't" into "don&#039;t" in a plain-text email.
        $misnamed = [];
        foreach ($this->sourceFiles(['*.twig']) as $file) {
            if (str_contains($file->getPathname(), '/Email/') && ! str_ends_with($file->getFilename(), '.html.twig') && ! str_ends_with($file->getFilename(), '.txt.twig')) {
                $misnamed[] = $this->relative($file->getPathname());
            }
        }

        self::assertSame([], $misnamed, 'Name plain-text email templates *.txt.twig');
    }

    public function testEveryKeyIsUsed(): void
    {
        $corpus = '';
        foreach ($this->sourceFiles(['*.php', '*.twig', '*.ts', '*.js', '*.yaml', '*.yml'], ['src', 'templates', 'assets', 'config']) as $file) {
            $corpus .= $file->getContents();
        }

        $unused = [];
        foreach (self::catalogProvider() as [$path]) {
            $domain = explode('.', basename($path))[0];
            $prefixes = self::DYNAMIC_PREFIXES[str_starts_with($domain, 'messages') ? 'messages' : $domain] ?? [];

            foreach (array_keys($this->flatten(Yaml::parseFile($path) ?? [])) as $key) {
                if (str_contains($corpus, $key)) {
                    continue;
                }

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($key, $prefix)) {
                        continue 2;
                    }
                }

                $unused[] = sprintf('%s: %s', $domain, $key);
            }
        }

        self::assertSame([], $unused, 'These keys are not referenced anywhere; delete them (or add their prefix to DYNAMIC_PREFIXES if built at runtime)');
    }

    /**
     * @param array<string, true> $known
     * @param list<string>        $errors
     */
    private function checkId(string $id, array $known, SplFileInfo $file, array &$errors, bool $mustBeKey): void
    {
        if ($id === '' || ! preg_match('/[a-zA-Z]/', $id)) {
            return;
        }

        $where = $this->relative($file->getPathname());

        if (preg_match(self::KEY_PATTERN, $id)) {
            if (! isset($known[$id])) {
                $errors[] = sprintf('%s: key "%s" is not defined in any catalog', $where, $id);
            }

            return;
        }

        // In PHP these option slots also carry technical values (placeholder="localhost", a
        // dynamic key prefix such as 'status.'); only text that reads as prose is reported.
        if ($mustBeKey || isset($known[$id]) || preg_match('/[A-Z\s]/', $id)) {
            $errors[] = sprintf('%s: "%s" is not a translation key (use a lowercase snake_case dotted key)', $where, $id);
        }
    }

    private function withoutComments(string $php): string
    {
        $code = '';
        foreach (token_get_all($php) as $token) {
            if (is_array($token)) {
                $code .= in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $token[1];
            } else {
                $code .= $token;
            }
        }

        return $code;
    }

    /**
     * @return list<array{int, string}>
     */
    private function hardCodedText(string $source): array
    {
        $blank = static fn (array $m): string => preg_replace('/[^\n]/', ' ', $m[0]) ?? '';

        $source = preg_replace_callback('/\{#.*?#\}|<!--.*?-->|<script\b.*?<\/script>|<style\b.*?<\/style>|\{%\s*verbatim\s*%\}.*?\{%\s*endverbatim\s*%\}/si', $blank, $source) ?? '';
        $source = preg_replace_callback('/\{\{.*?\}\}|\{%.*?%\}/s', $blank, $source) ?? '';

        $found = [];

        preg_match_all('/\s(?:title|placeholder|alt|aria-label|aria-description|data-bs-title|label)\s*=\s*"([^"]*[A-Za-z]{2,}[^"]*)"/', $source, $attributes, PREG_OFFSET_CAPTURE);
        foreach ($attributes[1] as [$text, $offset]) {
            $found[] = [substr_count($source, "\n", 0, $offset) + 1, trim($text)];
        }

        // Quoted attribute values may contain '>' (data-action="click->live#action").
        $text = preg_replace_callback('/<(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/s', $blank, $source) ?? '';
        foreach (explode("\n", $text) as $i => $line) {
            $line = trim(preg_replace('/&[a-z]+;|&#\d+;/', ' ', $line) ?? '');

            // Attribute fragments and CSS declarations emitted from Twig blocks are not prose.
            if (preg_match('/[A-Za-z]{2,}/', $line) && ! preg_match('/^[\w:-]+="|[\w-]+\s*:\s*[^;{}]+;/', $line)) {
                $found[] = [$i + 1, $line];
            }
        }

        return $found;
    }

    private function isAllowed(string $path, string $text): bool
    {
        foreach (self::TEMPLATE_TEXT_ALLOWLIST as $suffix => $patterns) {
            if ($suffix !== '*' && ! str_ends_with($path, $suffix)) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, true>
     */
    private function allKeys(): array
    {
        $keys = [];
        foreach (self::catalogProvider() as [$path]) {
            foreach (array_keys($this->flatten(Yaml::parseFile($path) ?? [])) as $key) {
                $keys[$key] = true;
            }
        }

        return $keys;
    }

    /**
     * @param array<mixed> $tree
     *
     * @return array<string, string>
     */
    private function flatten(array $tree, string $prefix = ''): array
    {
        $flat = [];
        foreach ($tree as $key => $value) {
            $id = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            $flat += is_array($value) ? $this->flatten($value, $id) : [$id => (string) $value];
        }

        return $flat;
    }

    /**
     * @param list<string> $names
     * @param list<string> $dirs
     *
     * @return iterable<SplFileInfo>
     */
    private function sourceFiles(array $names, array $dirs = ['src', 'templates']): iterable
    {
        return new Finder()
            ->files()
            ->in(array_map(static fn (string $dir): string => self::ROOT . '/' . $dir, $dirs))
            ->name($names)
            ->notPath(['#(^|/)Tests?/#', '#(^|/)DataFixtures/#', '#(^|/)node_modules/#'])
            ->notName(['*.css.twig', '*.js.twig']);
    }

    private function relative(string $path): string
    {
        $root = self::ROOT . '/';

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
