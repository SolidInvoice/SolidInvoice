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

namespace SolidInvoice\InstallBundle\Form\Step;

use Doctrine\DBAL\DriverManager;
use SolidInvoice\InstallBundle\Form\Type\DatabaseConfigType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Throwable;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Form\Step\ConfigStepFormTest
 */
class ConfigStepForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $drivers = $options['drivers'];

        $builder->add(
            'database_config',
            DatabaseConfigType::class,
            [
                'drivers' => $drivers,
                'constraints' => new Callback(
                    static function ($data, ExecutionContextInterface $executionContext): void {
                        if (null !== $data['driver'] && null !== $data['user']) {
                            try {
                                DriverManager::getConnection($data)->connect();
                            } catch (Throwable $e) {
                                $executionContext->addViolation($e->getMessage());
                            }
                        }
                    }
                ),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['drivers']);
    }

    public function getBlockPrefix(): string
    {
        return 'config_step';
    }
}
