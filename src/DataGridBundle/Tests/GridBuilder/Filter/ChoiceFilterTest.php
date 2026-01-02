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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Filter;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter
 */
final class ChoiceFilterTest extends TestCase
{
    private ChoiceFilter $filter;

    private QueryBuilder&MockObject $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->filter = ChoiceFilter::new('status', ['draft' => 'Draft', 'pending' => 'Pending', 'paid' => 'Paid']);
    }

    public function testFormReturnsChoiceType(): void
    {
        self::assertSame(ChoiceType::class, $this->filter->form());
    }

    public function testFormOptionsWithSingleChoice(): void
    {
        $options = $this->filter->formOptions();

        self::assertArrayHasKey('choices', $options);
        self::assertArrayHasKey('multiple', $options);
        self::assertArrayHasKey('placeholder', $options);
        self::assertFalse($options['multiple']);
        self::assertSame('Choose a value', $options['placeholder']);
        // Choices are flipped: label => value
        self::assertSame(['Draft' => 'draft', 'Pending' => 'pending', 'Paid' => 'paid'], $options['choices']);
    }

    public function testFormOptionsWithMultipleChoice(): void
    {
        $filter = ChoiceFilter::new('status', ['draft' => 'Draft', 'pending' => 'Pending'])
            ->multiple();

        $options = $filter->formOptions();

        self::assertTrue($options['multiple']);
        self::assertTrue($options['expanded']);
        self::assertArrayNotHasKey('placeholder', $options);
    }

    public function testFilterWithSingleValue(): void
    {
        $this->queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with(ORMSource::ALIAS . '.status = :status')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('status', 'draft');

        $this->filter->filter($this->queryBuilder, 'draft');
    }

    public function testFilterWithEmptyValueDoesNothing(): void
    {
        $this->queryBuilder
            ->expects(self::never())
            ->method('andWhere');

        $this->queryBuilder
            ->expects(self::never())
            ->method('setParameter');

        $this->filter->filter($this->queryBuilder, '');
    }

    public function testFilterWithMultipleValues(): void
    {
        $filter = ChoiceFilter::new('status', ['draft' => 'Draft', 'pending' => 'Pending'])
            ->multiple();

        $this->queryBuilder
            ->expects(self::once())
            ->method('andWhere')
            ->with(ORMSource::ALIAS . '.status IN (:status)')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('status', ['draft', 'pending']);

        $filter->filter($this->queryBuilder, ['draft', 'pending']);
    }

    public function testFilterWithEmptyArrayDoesNothing(): void
    {
        $filter = ChoiceFilter::new('status', ['draft' => 'Draft'])
            ->multiple();

        $this->queryBuilder
            ->expects(self::never())
            ->method('andWhere');

        $this->queryBuilder
            ->expects(self::never())
            ->method('setParameter');

        $filter->filter($this->queryBuilder, []);
    }

    public function testChoicesCanBeSetAfterConstruction(): void
    {
        $filter = ChoiceFilter::new('field', [])
            ->choices(['a' => 'A', 'b' => 'B']);

        $options = $filter->formOptions();
        self::assertSame(['A' => 'a', 'B' => 'b'], $options['choices']);
    }

    public function testMultipleCanBeDisabled(): void
    {
        $filter = ChoiceFilter::new('field', [])
            ->multiple(true)
            ->multiple(false);

        $options = $filter->formOptions();
        self::assertFalse($options['multiple']);
    }
}
