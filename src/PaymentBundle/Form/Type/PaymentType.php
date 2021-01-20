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

namespace SolidInvoice\PaymentBundle\Form\Type;

use Money\Money;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaymentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'payment_method',
            EntityType::class,
            [
                'class' => PaymentMethod::class,
                'query_builder' => function (PaymentMethodRepository $repository) use ($options) {
                    $queryBuilder = $repository->createQueryBuilder('pm');
                    $expression = $queryBuilder->expr();
                    $queryBuilder->where($expression->eq('pm.enabled', 1));

                    // If user is not logged in, exclude internal payment methods
                    if (null === $options['user']) {
                        $queryBuilder->andWhere(
                            $expression->orX(
                                $expression->neq('pm.internal', 1),
                                $expression->isNull('pm.internal')
                            )
                        );
                    }

                    $queryBuilder->orderBy($expression->asc('pm.name'));

                    return $queryBuilder;
                },
                'required' => true,
                'preferred_choices' => $options['preferred_choices'],
                'constraints' => new Assert\NotBlank(),
                'placeholder' => 'Choose Payment Method',
                'choice_attr' => function (PaymentMethod $paymentMethod) {
                    return ['data-gateway' => $paymentMethod->getGatewayName()];
                },
                'attr' => [
                    'class' => 'select2',
                ],
            ]
        );

        $builder->add(
            'amount',
            MoneyType::class,
            [
                'currency' => $options['currency'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Callback(function (Money $money, ExecutionContextInterface $context) {
                        if ($money->isZero() || $money->isNegative()) {
                            $context->buildViolation('This value should be greater than {{ compared_value }}.')
                                ->setParameter('{{ value }}', $money->getAmount())
                                ->setParameter('{{ compared_value }}', 0)
                                ->addViolation();
                        }
                    }),
                ],
            ]
        );

        if (null !== $options['user']) {
            $builder->add('capture_online', CheckboxType::class, ['data' => true]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['user', 'preferred_choices']);
        $resolver->setDefault('currency', null);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'payment';
    }
}
