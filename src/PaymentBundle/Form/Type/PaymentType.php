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

use Brick\Math\BigNumber;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Form\Type\PaymentTypeTest
 */
class PaymentType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'payment_method',
            EntityType::class,
            [
                'class' => PaymentMethod::class,
                'choices' => $this->registry->getRepository(PaymentMethod::class)->getAvailablePaymentMethods($options['user'] !== null),
                'required' => true,
                'preferred_choices' => $options['preferred_choices'],
                'constraints' => new Assert\NotBlank(),
                'placeholder' => 'Choose Payment Method',
                'choice_attr' => fn (PaymentMethod $paymentMethod) => ['data-gateway' => $paymentMethod->getGatewayName()],
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
                    new Assert\Callback(function (BigNumber $value, ExecutionContextInterface $context): void {
                        if ($value->isZero() || $value->isNegative()) {
                            $context->buildViolation('This value should be greater than {{ compared_value }}.')
                                ->setParameter('{{ value }}', (string) $value->toBigDecimal()->toFloat())
                                ->setParameter('{{ compared_value }}', '0')
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['user', 'preferred_choices']);
        $resolver->setDefault('currency', null);
        $resolver->setAllowedTypes('currency', ['null', Currency::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'payment';
    }
}
