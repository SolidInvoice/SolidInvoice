<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Form\Type;

use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class EmailSettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'transport',
            MailTransportType::class,
            [
                'placeholder' => 'Choose Mail Provider',
                'constraints' => new Constraints\Valid(),
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) {
            $data = $event->getData();

            $index = \strpos($data['transport']['transport'], '+');
            $type = substr($data['transport']['transport'], 0, false === $index ? \strlen($data['transport']['transport']) : $index).'Config';

            foreach ($data['transport'] as $key => &$value) {
                if (!str_ends_with($key, 'Config')) {
                    continue;
                }

                if ($key !== $type) {
                    foreach ($value as $k => $v) {
                        $value[$k] = null;
                    }
                }
            }

            $event->setData($data);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'validation_groups' => static function (FormInterface $form) {
                    $data = $form->getData();

                    $index = \strpos($data['transport']['transport'], '+');

                    if (false === $index) {
                        return ['Default', $data['transport']['transport']];
                    }

                    return ['Default', substr($data['transport']['transport'], 0, $index)];
                },
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'email_settings';
    }
}
