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

namespace SolidInvoice\SettingsBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class CheckBoxExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            public function transform(mixed $value): bool
            {
                return (bool) $value;
            }

            public function reverseTransform(mixed $value): string
            {
                return (string) $value;
            }
        });
    }

    public static function getExtendedTypes(): iterable
    {
        yield CheckboxType::class;
    }
}
