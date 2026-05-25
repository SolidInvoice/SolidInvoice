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

namespace SolidInvoice\SettingsBundle\Form\Type;

use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType as CFType;
use SolidInvoice\CoreBundle\Enum\CustomFieldVisibility;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use function array_column;
use function in_array;
use function is_array;
use function is_string;
use function strtolower;

/**
 * @extends AbstractType<CustomField>
 */
final class CustomFieldDefinitionType extends AbstractType
{
    public function __construct(
        private readonly CustomFieldTypeResolver $resolver,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $entity = null;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use (&$entity): void {
            $entity = $event->getData();
        });

        $builder
            ->add('target', EnumType::class, [
                'class' => CustomFieldTarget::class,
                'choice_label' => static fn (CustomFieldTarget $t): string => $t->label(),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Applies to',
                'help' => 'Choose where this field should appear.',
                'disabled' => $options['lock_target'],
            ])
            ->add('label', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 125)],
            ])
            ->add('type', EnumType::class, [
                'class' => CFType::class,
                'choice_label' => static fn (CFType $t): string => $t->label(),
            ])
            ->add('required', CheckboxType::class, ['required' => false]);

        $builder->addDependent('visibility', ['target'], static function (DependentField $field, ?CustomFieldTarget $target): void {
            if (! $target instanceof CustomFieldTarget || ! $target->supportsVisibility()) {
                return;
            }

            $field->add(EnumType::class, [
                'class' => CustomFieldVisibility::class,
                'choice_label' => static fn (CustomFieldVisibility $v): string => $v->label(),
                'expanded' => true,
                'multiple' => false,
                'label' => 'Visibility',
                'help' => 'Internal fields appear only on admin views. Client-visible fields also appear on PDF and online views.',
                'placeholder' => false,
            ]);
        });

        $builder->addDependent('options', ['type'], static function (DependentField $field, ?CFType $type): void {
            if ($type !== CFType::SELECT && $type !== CFType::MULTI_SELECT) {
                return;
            }

            $field->add(LiveCollectionType::class, [
                'entry_type' => CustomFieldOptionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'button_add_options' => [
                    'label' => 'Add option',
                    'attr' => ['class' => 'btn btn-sm btn-outline-primary'],
                ],
                'button_delete_options' => [
                    'label' => false,
                    'attr' => ['class' => 'btn btn-link text-danger btn-icon btn-icon-sm'],
                ],
            ]);
        });

        $builder->addDependent('defaultValue', ['type'], function (DependentField $field, ?CFType $type) use (&$entity): void {
            if (! $type instanceof CFType) {
                return;
            }

            $rawOptions = $entity instanceof CustomField ? ($entity->getOptions() ?? []) : [];
            $validOptions = $this->normaliseOptions($rawOptions);

            if (in_array($type, [CFType::SELECT, CFType::MULTI_SELECT], true) && $validOptions === []) {
                return;
            }

            $tempField = new CustomField()->setType($type)->setOptions($validOptions);
            [$class, $opts] = $this->resolver->formTypeAndOptions($tempField);
            $opts['label'] = 'Default value';
            $opts['help'] = 'Optional — pre-fills new records. Leave empty for no default.';
            $opts['required'] = false;
            $opts['mapped'] = false;

            if (in_array($type, [CFType::SELECT, CFType::MULTI_SELECT], true)) {
                $opts['placeholder'] = 'No default';
            }

            if ($type === CFType::MULTI_SELECT) {
                unset($opts['placeholder']);
                $opts['choices'] = array_combine(array_column($validOptions, 'label'), array_column($validOptions, 'value'));
            }

            if ($entity instanceof CustomField && $entity->getDefaultValue() !== null) {
                $opts['data'] = $this->resolver->deserialize($entity, $entity->getDefaultValue());
            }

            $field->add($class, $opts);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $field = $event->getData();
            if (! $field instanceof CustomField) {
                return;
            }

            $form = $event->getForm();
            if (! $form->has('options')) {
                $field->setOptions(null);
            }

            if (! $form->has('visibility')) {
                $field->setVisibility(null);
            }

            if (! $form->has('defaultValue')) {
                $field->setDefaultValue(null);
                return;
            }

            $field->setDefaultValue($this->resolver->serialize($field, $form->get('defaultValue')->getData()));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomField::class,
            'lock_target' => false,
        ]);
        $resolver->setAllowedTypes('lock_target', 'bool');
    }

    /**
     * @param array<int|string, mixed> $options
     * @return list<array{label: string, value: string}>
     */
    private function normaliseOptions(array $options): array
    {
        $out = [];
        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }

            $label = $opt['label'] ?? null;
            if (! is_string($label) || $label === '') {
                continue;
            }

            $value = $opt['value'] ?? null;
            if (! is_string($value) || $value === '') {
                $value = strtolower($this->slugger->slug($label, '_')->toString());
            }

            $out[] = ['label' => $label, 'value' => $value];
        }

        return $out;
    }
}
