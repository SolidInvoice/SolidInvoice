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

use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\EntityFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Filter\EntityFilter
 */
final class EntityFilterTest extends TestCase
{
    private EntityFilter $filter;

    protected function setUp(): void
    {
        $this->filter = EntityFilter::new('App\\Entity\\Client', 'clients', 'name');
    }

    public function testFormReturnsEntityType(): void
    {
        self::assertSame(EntityType::class, $this->filter->form());
    }

    public function testFormOptionsWithSingleChoice(): void
    {
        $options = $this->filter->formOptions();

        self::assertArrayHasKey('class', $options);
        self::assertArrayHasKey('multiple', $options);
        self::assertArrayHasKey('placeholder', $options);
        self::assertArrayHasKey('choice_name', $options);
        self::assertArrayHasKey('choice_value', $options);

        self::assertSame('App\\Entity\\Client', $options['class']);
        self::assertFalse($options['multiple']);
        self::assertSame('Choose a value', $options['placeholder']);
        self::assertSame('name', $options['choice_name']);
        self::assertIsCallable($options['choice_value']);
    }

    public function testFormOptionsWithMultipleChoice(): void
    {
        $filter = EntityFilter::new('App\\Entity\\Client', 'clients', 'name')
            ->multiple();

        $options = $filter->formOptions();

        self::assertTrue($options['multiple']);
    }

    public function testMultipleCanBeDisabled(): void
    {
        $filter = EntityFilter::new('App\\Entity\\Client', 'clients', 'name')
            ->multiple(true)
            ->multiple(false);

        $options = $filter->formOptions();
        self::assertFalse($options['multiple']);
    }

    public function testChoiceValueCallbackReturnsStringForStringInput(): void
    {
        $options = $this->filter->formOptions();
        $callback = $options['choice_value'];

        self::assertSame('test-string', $callback('test-string'));
    }

    public function testChoiceValueCallbackReturnsIdForObject(): void
    {
        $options = $this->filter->formOptions();
        $callback = $options['choice_value'];

        $entity = new class() {
            public function getId(): string
            {
                return 'entity-id-123';
            }
        };

        self::assertSame('entity-id-123', $callback($entity));
    }
}
