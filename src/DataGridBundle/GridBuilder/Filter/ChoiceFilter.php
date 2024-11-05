<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Filter;

use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use function array_flip;
use function is_string;

final class ChoiceFilter implements ColumnFilterInterface
{
    private bool $multiple = false;

    /**
     * @param array<string, string|int|bool> $choices
     */
    public function __construct(
        private readonly string $field,
        private array $choices,
    ) {
    }

    /**
     * @param array<string, string|int|bool> $choices
     */
    public static function new(string $field, array $choices = []): self
    {
        return new self($field, $choices);
    }

    /**
     * @param array<string, string|int|bool> $choices
     */
    public function choices(array $choices): self
    {
        $this->choices = $choices;

        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function form(): string
    {
        return ChoiceType::class;
    }

    public function formOptions(): array
    {
        return [
            'placeholder' => 'Choose a value',
            'multiple' => $this->multiple,
            'choices' => array_flip($this->choices),
        ];
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        if ($this->multiple) {
            assert(is_array($value));

            if ([] !== $value) {
                $queryBuilder->andWhere(sprintf('%s.%s IN (:%s)', ORMSource::ALIAS, $this->field, $this->field))
                    ->setParameter($this->field, $value);
            }
        } else {
            assert(is_string($value));

            if ('' !== $value) {
                $queryBuilder->andWhere(sprintf('%s.%s = :%s', ORMSource::ALIAS, $this->field, $this->field))
                    ->setParameter($this->field, $value);
            }
        }
    }
}
