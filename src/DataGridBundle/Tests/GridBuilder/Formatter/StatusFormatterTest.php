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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Formatter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\StatusFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Formatter\StatusFormatter
 */
final class StatusFormatterTest extends TestCase
{
    private StatusFormatter $formatter;

    private TranslatorInterface&MockObject $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);

        $this->formatter = new StatusFormatter($this->translator);
    }

    public function testFormatReturnsStringForNonStatusColumn(): void
    {
        $column = StringColumn::new('name');

        self::assertSame('test value', $this->formatter->format($column, 'test value'));
    }

    public function testFormatReturnsEmptyStringForNullValue(): void
    {
        $column = StatusColumn::new('status');

        self::assertSame('', $this->formatter->format($column, null));
    }

    public function testFormatReturnsEmptyStringForEmptyValue(): void
    {
        $column = StatusColumn::new('status');

        self::assertSame('', $this->formatter->format($column, ''));
    }

    public function testFormatReturnsStyledBadge(): void
    {
        $column = StatusColumn::new('status')
            ->statusMap(['paid' => 'success']);

        $result = $this->formatter->format($column, 'paid');

        self::assertStringContainsString('class="datagrid-status status-success"', $result);
        self::assertStringContainsString('Paid', $result);
    }

    public function testFormatUsesDefaultVariantWhenNotInMap(): void
    {
        $column = StatusColumn::new('status')
            ->statusMap(['paid' => 'success'])
            ->variant('info');

        $result = $this->formatter->format($column, 'unknown');

        self::assertStringContainsString('status-info', $result);
    }

    public function testFormatAppliesTitleCase(): void
    {
        $column = StatusColumn::new('status')
            ->titleCase(true);

        $result = $this->formatter->format($column, 'pending_approval');

        self::assertStringContainsString('Pending Approval', $result);
    }

    public function testFormatDoesNotApplyTitleCaseWhenDisabled(): void
    {
        $column = StatusColumn::new('status')
            ->titleCase(false);

        $result = $this->formatter->format($column, 'PENDING');

        self::assertStringContainsString('PENDING', $result);
    }

    public function testFormatEscapesHtmlInValue(): void
    {
        $column = StatusColumn::new('status');

        $result = $this->formatter->format($column, '<script>alert("xss")</script>');

        self::assertStringNotContainsString('<script>', $result);
        self::assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testFormatTranslatesLabel(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('Paid')
            ->willReturn('Bezahlt');

        $formatter = new StatusFormatter($this->translator);
        $column = StatusColumn::new('status');

        $result = $formatter->format($column, 'paid');

        self::assertStringContainsString('Bezahlt', $result);
    }

    public function testFormatHandlesHyphenatedStatus(): void
    {
        $column = StatusColumn::new('status')
            ->titleCase(true);

        $result = $this->formatter->format($column, 'in-progress');

        self::assertStringContainsString('In Progress', $result);
    }
}
