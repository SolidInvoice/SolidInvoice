<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Step;

use Doctrine\DBAL\DBALException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class DatabaseConfigForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $drivers = $options['drivers'];

        $builder->add(
            'driver',
            'choice',
            array(
                'help' => 'Only MySQL database is supported at the moment',
                'choices' => $drivers,
                'constraints' => array(
                     new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'host',
            null,
            array(
                'data' => $options['host'],
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'port',
            null,
            array(
                'data' => $options['port'],
            )
        );

        $builder->add(
            'user',
            null,
            array(
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'password',
            'password'
        );

        $builder->add(
            'name',
            null,
            array(
                'label' => 'Database Name',
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            )
        );

        $connectionFactory = $options['connection_factory'];

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($connectionFactory, $drivers) {
                $form = $event->getForm();

                if ($form->isValid()) {
                    $data = $form->getData();

                    $connection = $connectionFactory->createConnection(array(
                        'driver' => sprintf('pdo_%s', $drivers[$data['driver']]),
                        'user' => $data['user'],
                        'password' => $data['password'],
                        'host' => $data['host'],
                        'dbname' => $data['name'],
                    ));

                    try {
                        $connection->connect();
                    } catch (DBALException $e) {
                        $form->addError(new FormError($e->getMessage()));
                    }
                }
            }
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'drivers',
            'host',
            'port',
            'connection_factory',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'database_config';
    }
}
