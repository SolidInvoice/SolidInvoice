<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Form\FormFlow;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Flow\FormFlowCursor;
use Symfony\Component\Form\Flow\Type\FinishFlowType;
use Symfony\Component\Form\Flow\Type\NextFlowType;
use Symfony\Component\Form\Flow\Type\PreviousFlowType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InstallNavigatorType extends AbstractType
{
    public function __construct(
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'previous',
            PreviousFlowType::class,
            [
                'include_if' => fn (FormFlowCursor $cursor) => ! $cursor->isLastStep() && $cursor->canMoveBack(),
            ]
        );
        $builder->add(
            'next',
            NextFlowType::class,
            [
                'include_if' => fn (FormFlowCursor $cursor): bool => $cursor->getCurrentStep() !== 'review' && $cursor->canMoveNext(),
            ]
        );
        $builder->add(
            'install',
            InstallFlowType::class,
        );
        $builder->add(
            'finish',
            FinishFlowType::class,
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'mapped' => false,
            'priority' => -100,
        ]);
    }
}
