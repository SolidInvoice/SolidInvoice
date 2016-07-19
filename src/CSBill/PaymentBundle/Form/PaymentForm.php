<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Form;

use CSBill\PaymentBundle\Repository\PaymentMethodRepository;
use Money\Money;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaymentForm extends AbstractType
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
                'class' => 'CSBillPaymentBundle:PaymentMethod',
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
                'attr' => [
                    'class' => 'select2',
                ],
            ]
        );

        $builder->add(
            'amount',
            MoneyType::class,
            [
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
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'payment';
    }
}
