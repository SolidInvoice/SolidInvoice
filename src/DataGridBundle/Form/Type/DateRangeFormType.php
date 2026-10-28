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

namespace SolidInvoice\DataGridBundle\Form\Type;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\Form\Type\DateRangeFormTypeTest
 * @extends AbstractType<array{end: mixed, start: mixed}>
 */
final class DateRangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'start',
            DateType::class,
            [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'datagrid.filter.date_range.start',
                'required' => false,
            ]
        )
            ->add(
                'end',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'html5' => true,
                    'label' => 'datagrid.filter.date_range.end',
                    'required' => false,
                ]
            );

        $dateTransformer = new DateTimeToStringTransformer(format: 'Y-m-d');
        $transformer = new CallbackTransformer(
            fn ($value) => $dateTransformer->reverseTransform($value),
            fn ($value) => $dateTransformer->transform($value)
        );

        $builder->get('start')->addModelTransformer($transformer);
        $builder->get('end')->addModelTransformer($transformer);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'date_range';
    }
}
