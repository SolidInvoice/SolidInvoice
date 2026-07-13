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

namespace SolidInvoice\CoreBundle\Translation\Extractor;

use LogicException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Extracts translatable labels from menu builders.
 *
 * Menu builders pass their labels as the first argument of KnpMenu's `addChild()`
 * (e.g. `$menu->addChild('client.menu.main', ...)`), which the rendered template then
 * passes through the `trans` filter. Those calls contain none of the tokens
 * ({@see \Symfony\Component\Translation\Extractor\PhpAstExtractor} looks for `->trans(`,
 * `t(`, `TranslatableMessage`, validator constraints), so the built-in AST extractor
 * skips menu-builder files entirely. This extractor closes that gap.
 *
 * It is intentionally self-contained and only depends on the `#[MenuBuilder]` attribute
 * and KnpMenu's `addChild()` contract, so it can be promoted to solidworx/platform as a
 * platform-level integration without modification.
 * @see \SolidInvoice\CoreBundle\Tests\Translation\Extractor\MenuLabelExtractorTest
 */
final class MenuLabelExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    private readonly Parser $parser;

    private string $prefix = '';

    public function __construct()
    {
        if (! class_exists(ParserFactory::class)) {
            throw new LogicException(\sprintf('You cannot use "%s" as the "nikic/php-parser" package is not installed. Try running "composer require nikic/php-parser".', self::class));
        }

        $this->parser = new ParserFactory()->createForHostVersion();
    }

    public function extract(iterable | string $resource, MessageCatalogue $catalogue): void
    {
        foreach ($this->extractFiles($resource) as $file) {
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());

            $visitor = new MenuLabelVisitor();
            $visitor->initialize($catalogue, $file, $this->prefix);
            $traverser->addVisitor($visitor);

            $nodes = $this->parser->parse(file_get_contents((string) $file) ?: '') ?? [];
            $traverser->traverse($nodes);
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    protected function canBeExtracted(string $file): bool
    {
        if ('php' !== pathinfo($file, \PATHINFO_EXTENSION) || ! $this->isFile($file)) {
            return false;
        }

        $contents = file_get_contents($file) ?: '';

        return str_contains($contents, 'MenuBuilder') && str_contains($contents, 'addChild(');
    }

    /**
     * @param string|array<string> $resource
     *
     * @return iterable<SplFileInfo>
     */
    protected function extractFromDirectory(array | string $resource): iterable
    {
        return new Finder()->files()->name('*.php')->in($resource);
    }
}
