<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Form\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function str_replace;
use function trim;

final class UnsanitizeSingleQuotesTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['allow_single_quotes' => false])
            ->setAllowedTypes('allow_single_quotes', 'bool')
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['allow_single_quotes']) {
            return;
        }

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            static function (FormEvent $event): void {
                if (\is_scalar($data = $event->getData()) && '' !== trim($data)) {
                    $event->setData(str_replace('&#039;', "'", $data));
                }
            },
            10000 - 1
        );
    }
}
