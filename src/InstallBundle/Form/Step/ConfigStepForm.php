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

namespace SolidInvoice\InstallBundle\Form\Step;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use PDOException;
use SolidInvoice\InstallBundle\Form\Type\DatabaseConfigType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfigStepForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $drivers = $options['drivers'];

        $builder->add(
            'database_config',
            DatabaseConfigType::class,
            [
                'drivers' => $drivers,
                'constraints' => new Callback(
                    static function ($data, ExecutionContextInterface $executionContext) {
                        if (null !== $data['driver'] && null !== $data['user']) {
                            try {
                                DriverManager::getConnection($data)->connect();
                            } catch (PDOException | DBALException $e) {
                                $executionContext->addViolation($e->getMessage());
                            }
                        }
                    }
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['drivers']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'config_step';
    }
}
