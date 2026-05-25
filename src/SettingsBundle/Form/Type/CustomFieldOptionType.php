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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use function is_array;
use function strtolower;

/**
 * @extends AbstractType<array{label: string}>
 */
final class CustomFieldOptionType extends AbstractType
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => false,
            'attr' => ['placeholder' => 'Option label'],
            'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 125)],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (! is_array($data)) {
                return;
            }

            $label = $data['label'] ?? null;
            if (! is_string($label) || $label === '') {
                $event->setData(null);
                return;
            }

            $data['value'] = strtolower($this->slugger->slug($label, '_')->toString());
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', null);
    }
}
