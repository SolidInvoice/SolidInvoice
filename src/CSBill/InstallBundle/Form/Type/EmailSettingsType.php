<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class EmailSettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transports = $options['transports'];

        $builder->add(
            'transport',
            'select2',
            array(
                'choices' => array_flip($transports),
                'choices_as_values' => true,
                'placeholder' => 'Choose Mail Transport',
                'constraints' => array(
                     new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'host',
            null,
            array(
                'constraints' => array(
                    new Constraints\NotBlank(array('groups' => 'smtp')),
                ),
            )
        );

        $builder->add(
            'port',
            'integer',
            array(
                'constraints' => array(
                    new Constraints\Type(array('groups' => array('smtp'), 'type' => 'integer')),
                ),
                'required' => false,
            )
        );

        $builder->add(
            'encryption',
            'select2',
            array(
                'placeholder' => 'None',
                'choices' => array(
                    'SSL' => 'ssl',
                    'TLS' => 'tls',
                ),
                'choices_as_values' => true,
                'required' => false,
            )
        );

        $builder->add(
            'user',
            null,
            array(
                'constraints' => array(
                    new Constraints\NotBlank(array('groups' => 'gmail')),
                ),
                'required' => false,
            )
        );

        $builder->add(
            'password',
            'password',
            array(
                'constraints' => array(
                    new Constraints\NotBlank(array('groups' => 'gmail')),
                ),
                'required' => false,
            )
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if ('gmail' === $data['transport']) {
                $data['host'] = null;
                $data['port'] = null;
                $data['encryption'] = null;
            } elseif ('sendmail' === $data['transport'] || 'mail' === $data['transport']) {
                $data['host'] = null;
                $data['port'] = null;
                $data['encryption'] = null;
                $data['user'] = null;
                $data['password'] = null;
            }

            $event->setData($data);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('transports'));

        $resolver->setDefaults(
            array(
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();

                    if ('smtp' === $data['transport']) {
                        return array('Default', 'smtp');
                    }

                    if ('gmail' === $data['transport']) {
                        return array('Default', 'gmail');
                    }

                    return array('Default');
                },
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'email_settings';
    }
}
