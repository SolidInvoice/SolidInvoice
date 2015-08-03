<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Step;

use CSBill\InstallBundle\Form\Type\DatabaseConfigType;
use CSBill\InstallBundle\Form\Type\EmailSettingsType;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfigStepForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $drivers = $options['drivers'];

        $builder->add(
            'database_config',
            new DatabaseConfigType(),
            array(
                'drivers' => $drivers,
                'constraints' => new Constraints\Callback(
                    function ($data, ExecutionContextInterface $executionContext) {
                        if (null !== $data['driver'] && null !== $data['user']) {
                            try {
                                DriverManager::getConnection($data)->connect();
                            } catch (\PDOException $e) {
                                $executionContext->addViolation($e->getMessage());
                            }
                        }
                    }
                ),
            )
        );

        $builder->add(
            'email_settings',
            new EmailSettingsType(),
            array(
                'transports' => $options['mailer_transports'],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'drivers',
                'mailer_transports',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config_step';
    }
}
