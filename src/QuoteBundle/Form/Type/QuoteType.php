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

namespace SolidInvoice\QuoteBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use JsonException;
use Money\Currency;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\ClientAutocompleteType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Form\Type\QuoteTypeTest
 */
class QuoteType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add(
            'client',
            ClientAutocompleteType::class,
            [
                'attr' => [
                    'class' => 'client-select',
                ],
                'placeholder' => 'quote.client.choose',
            ]
        );

        $builder->add(
            'discount',
            DiscountType::class,
            [
                'required' => false,
                'label' => 'Discount',
                'currency' => $options['currency']
            ]
        );

        $builder->add(
            'lines',
            LiveCollectionType::class,
            [
                'entry_type' => ItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'entry_options' => [
                    'currency' => $options['currency'],
                ],
            ]
        );

        $data = $options['data']?->getQuoteId() ?: $this->billingIdGenerator->generate($options['data'] ?? new Quote(), ['field' => 'quoteId']);
        $builder->add('quoteId', null, ['data' => $data]);

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);

        $builder->addDependent('users', 'client', function (DependentField $field, ?Client $client): void {
            if (! $client instanceof Client) {
                return;
            }

            $field->add(
                null,
                [
                    'constraints' => new NotBlank(),
                    'expanded' => true,
                    'multiple' => true,
                    'query_builder' => function (EntityRepository $repo) use ($client) {
                        return $repo->createQueryBuilder('c')
                            ->where('c.client = :client')
                            ->setParameter('client', $client->getId(), UlidType::NAME);
                    },
                ]
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Quote::class,
                'currency' => $this->systemConfig->getCurrency(),
            ]
        )
            ->setAllowedTypes('currency', [Currency::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'quote';
    }
}
