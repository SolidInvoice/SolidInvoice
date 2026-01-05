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

final class RecurringScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('type', EnumType::class, [
                'class' => ScheduleRecurringType::class,
                'placeholder' => 'invoice.recurring.type.placeholder',
                'expanded' => true,
                'label' => 'invoice.recurring.type.label',
            ]);

        $builder->addDependent('days', ['type'], function (DependentField $field, ?ScheduleRecurringType $recurringType): void {
            switch ($recurringType) {
                case ScheduleRecurringType::WEEKLY:
                    $field->add(ChoiceType::class, [
                        'choices' => [
                            'invoice.recurring.days.monday' => 0,
                            'invoice.recurring.days.tuesday' => 1,
                            'invoice.recurring.days.wednesday' => 2,
                            'invoice.recurring.days.thursday' => 3,
                            'invoice.recurring.days.friday' => 4,
                            'invoice.recurring.days.saturday' => 5,
                            'invoice.recurring.days.sunday' => 6,
                        ],
                        'multiple' => true,
                        'expanded' => true,
                        'label' => 'invoice.recurring.repeats_on',
                        'choice_translation_domain' => 'messages',
                    ]);
                    break;
                case ScheduleRecurringType::MONTHLY:
                    $monthlyChoices = [];
                    for ($day = 1; $day <= 31; $day++) {
                        $monthlyChoices[$this->formatOrdinal($day)] = $day;
                    }

                    $field->add(ChoiceType::class, [
                        'choices' => $monthlyChoices,
                        'multiple' => true,
                        'expanded' => false,
                        'label' => 'invoice.recurring.days_of_month',
                        'attr' => ['class' => 'form-select'],
                    ]);
                    break;
                case ScheduleRecurringType::YEARLY:
                    $field->add(ChoiceType::class, [
                        'label' => 'invoice.recurring.repeats_in_months',
                        'choices' => [
                            'invoice.recurring.months.january' => 1,
                            'invoice.recurring.months.february' => 2,
                            'invoice.recurring.months.march' => 3,
                            'invoice.recurring.months.april' => 4,
                            'invoice.recurring.months.may' => 5,
                            'invoice.recurring.months.june' => 6,
                            'invoice.recurring.months.july' => 7,
                            'invoice.recurring.months.august' => 8,
                            'invoice.recurring.months.september' => 9,
                            'invoice.recurring.months.october' => 10,
                            'invoice.recurring.months.november' => 11,
                            'invoice.recurring.months.december' => 12,
                        ],
                        'multiple' => true,
                        'expanded' => true,
                        'choice_translation_domain' => 'messages',
                    ]);
                    break;
                case ScheduleRecurringType::DAILY:
                    // no-op
            }
        });

        $builder->addDependent('dayOfTheMonth', ['recurringType'], function (DependentField $field, ?ScheduleRecurringType $recurringType): void {
            if ($recurringType === ScheduleRecurringType::YEARLY) {
                $yearlyDayChoices = [];
                for ($day = 1; $day <= 31; $day++) {
                    $yearlyDayChoices[$this->formatOrdinal($day)] = $day;
                }

                $field->add(ChoiceType::class, [
                    'choices' => $yearlyDayChoices,
                    'required' => false,
                    'placeholder' => 'invoice.recurring.day_of_month.placeholder',
                    'multiple' => false,
                    'expanded' => false,
                    'label' => 'invoice.recurring.day_of_month',
                    'attr' => ['class' => 'form-select'],
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
                        'label' => 'invoice.recurring.end_after_occurrences',
                        'attr' => [
                            'min' => 1,
                            'placeholder' => 'invoice.recurring.end_occurrence_placeholder',
                        ],
                        'html5' => true,
                        'empty_data' => '1',
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
                        'label' => 'invoice.recurring.end_date',
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
            'label' => 'invoice.recurring.end_type',
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

    private function formatOrdinal(int $number): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::ORDINAL);
            $formatted = $formatter->format($number);

            if (false !== $formatted) {
                return $formatted;
            }
        }

        // Fallback to plain number if NumberFormatter is not available
        return (string) $number;
    }
}
