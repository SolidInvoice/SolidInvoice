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

namespace SolidInvoice\CoreBundle\Tests\Translation\Extractor;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Translation\Extractor\MenuLabelExtractor;
use Symfony\Component\Translation\MessageCatalogue;

final class MenuLabelExtractorTest extends TestCase
{
    private MenuLabelExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new MenuLabelExtractor();
    }

    public function testItExtractsMenuLabelsFromMenuBuilderFile(): void
    {
        $catalogue = new MessageCatalogue('en');

        $this->extractor->extract(__DIR__ . '/../../Fixtures/Menu/SampleMenu.php', $catalogue);

        $messages = $catalogue->all('messages');

        self::assertArrayHasKey('sample.menu.main', $messages);
        self::assertArrayHasKey('sample.menu.list', $messages);
        // A literal "label" option overrides the name, so the label is extracted...
        self::assertArrayHasKey('sample.menu.add.label', $messages);
        // ...and the name it overrides is not.
        self::assertArrayNotHasKey('sample.menu.add', $messages);
        // A dynamic label produces nothing translatable.
        self::assertArrayNotHasKey('dynamic', $messages);
    }

    public function testItPrefixesNewMessagesWithTheirOwnValue(): void
    {
        $catalogue = new MessageCatalogue('en');
        $this->extractor->setPrefix('__');

        $this->extractor->extract(__DIR__ . '/../../Fixtures/Menu/SampleMenu.php', $catalogue);

        self::assertSame('__sample.menu.main', $catalogue->get('sample.menu.main', 'messages'));
    }

    public function testItIgnoresPhpFilesWithoutMenuBuilders(): void
    {
        $catalogue = new MessageCatalogue('en');

        $this->extractor->extract(__FILE__, $catalogue);

        self::assertSame([], $catalogue->all('messages'));
    }

    public function testItExtractsFromADirectory(): void
    {
        $catalogue = new MessageCatalogue('en');

        $this->extractor->extract(__DIR__ . '/../../Fixtures/Menu', $catalogue);

        self::assertArrayHasKey('sample.menu.main', $catalogue->all('messages'));
    }
}
