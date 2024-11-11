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

namespace SolidInvoice\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @see \SolidInvoice\NotificationBundle\Tests\Form\Type\NotificationTypeTest
 */
class NotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', CheckboxType::class);
        $builder->add('sms', CheckboxType::class);

        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            public function transform(mixed $value): mixed
            {
                if (! is_string($value)) {
                    return null;
                }

                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }

            public function reverseTransform(mixed $value): string | null | false
            {
                if (null === $value) {
                    return $value;
                }

                return json_encode($value, JSON_THROW_ON_ERROR);
            }
        });
    }

    public function getBlockPrefix(): string
    {
        return 'notification';
    }
}
