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

namespace SolidInvoice\CronBundle\Form\Type;

use Carbon\CarbonImmutable;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use function array_combine;
use function range;

final class RecurringScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('type', EnumType::class, [
                'class' => ScheduleRecurringType::class,
                'placeholder' => 'Select a recurring type',
                'expanded' => true,
                'label' => 'Recurring Type',
            ]);

        $builder->addDependent('days', ['type'], function (DependentField $field, ?ScheduleRecurringType $recurringType): void {
            switch ($recurringType) {
                case ScheduleRecurringType::WEEKLY:
                    $field->add(ChoiceType::class, [
                        'choices' => [
                            'Monday' => 0,
                            'Tuesday' => 1,
                            'Wednesday' => 3,
                            'Thursday' => 4,
                            'Friday' => 5,
                            'Saturday' => 6,
                            'Sunday' => 7,
                        ],
                        'multiple' => true,
                        'expanded' => true,
                    ]);
                    break;
                case ScheduleRecurringType::MONTHLY:
                    $monthlyChoices = array_combine(range(1, 29), range(1, 29));
                    // $monthlyChoices['Last day of the month'] = -1;

                    $field->add(ChoiceType::class, [
                        'choices' => $monthlyChoices,
                        'multiple' => true,
                        'expanded' => false,
                        'label' => 'Days of the month',
                    ]);
                    break;
                case ScheduleRecurringType::YEARLY:
                    $yearChoices = [
                        'January' => 1,
                        'February' => 2,
                        'March' => 3,
                        'April' => 4,
                        'May' => 5,
                        'June' => 6,
                        'July' => 7,
                        'August' => 8,
                        'September' => 9,
                        'October' => 10,
                        'November' => 11,
                        'December' => 12,
                    ];
                    $field->add(ChoiceType::class, [
                        'label' => 'Months',
                        'choices' => $yearChoices,
                        'multiple' => true,
                        'expanded' => true,
                    ]);
                    break;
                case ScheduleRecurringType::DAILY:
                    // no-op
            }
        });

        $builder->addDependent('dayOfTheMonth', ['recurringType'], function (DependentField $field, ?ScheduleRecurringType $recurringType): void {
            if ($recurringType === ScheduleRecurringType::YEARLY) {
                $field->add(ChoiceType::class, [
                    'choices' => array_combine(range(1, 31), range(1, 31)),
                    'required' => false,
                    'placeholder' => false,
                    'multiple' => false,
                    'expanded' => false,
                ]);
            }
        });

        $builder->addDependent('endOccurrence', ['endType'], function (DependentField $field, ?ScheduleEndType $endType): void {
            if ($endType === null) {
                return;
            }

            if ($endType->isAfter()) {
                $field->add(
                    NumberType::class,
                    [
                        'label' => 'End After x Occurrences',
                        'attr' => ['min' => 0],
                        'html5' => true,
                        'empty_data' => '0',
                        'required' => false,
                    ]
                );
            }
        });

        $builder->addDependent('endDate', ['endType'], function (DependentField $field, ?ScheduleEndType $endType): void {
            if ($endType === null) {
                return;
            }

            if ($endType->isOn()) {
                $field->add(
                    DateType::class,
                    [
                        'label' => 'End Date',
                        'required' => false,
                        'input' => 'datetime_immutable',
                        'attr' => [
                            'min' => CarbonImmutable::now()
                                ->addDay()
                                ->format('Y-m-d'),
                        ],
                    ]
                );
            }
        });

        $builder->add('endType', EnumType::class, [
            'label' => 'End Recurrence',
            'class' => ScheduleEndType::class,
            'choice_label' => static fn (ScheduleEndType $type) => $type->formLabel(),
            'expanded' => true,
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', RecurringOptions::class);
    }
}
