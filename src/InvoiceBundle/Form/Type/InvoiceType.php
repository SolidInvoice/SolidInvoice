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

namespace SolidInvoice\InvoiceBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use JsonException;
use Money\Currency;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\ClientAutocompleteType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\InvoiceTypeTest
 */
class InvoiceType extends AbstractType
{
    /**
     * Initializes the InvoiceType form with system configuration and billing ID generation services.
     *
     * @internal
     */
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * Builds the invoice form with dynamic fields, including client selection, discount, line items, monetary totals, and dependent user assignment.
     *
     * Adds fields for client, discount, invoice lines, invoice ID (auto-generated if not present), terms, notes, totals, tax, invoice and due dates. Dynamically adds a multi-select users field based on the selected client, restricting choices to users associated with that client.
     *
     * @throws ContainerExceptionInterface If a required service cannot be retrieved from the container.
     * @throws NotFoundExceptionInterface If a required service is not found in the container.
     * @throws JsonException If there is an error encoding or decoding JSON data.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add(
            'client',
            ClientAutocompleteType::class,
            [
                'placeholder' => 'invoice.client.choose',
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

        $invoice = $options['data'] ?? new Invoice();

        $data = $invoice->getInvoiceId() ?: $this->billingIdGenerator->generate($invoice, ['field' => 'invoiceId']);

        $builder->add('invoiceId', null, ['data' => $data]);

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('invoiceDate', DateType::class, ['widget' => 'single_text', 'input' => 'datetime_immutable']);
        $builder->add('due', DateType::class, ['widget' => 'single_text', 'label' => 'Due Date', 'required' => false, 'input' => 'datetime_immutable']);

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

    /****
     * Returns the prefix used for the names of form fields in the invoice form.
     *
     * @return string The block prefix 'invoice'.
     */
    public function getBlockPrefix(): string
    {
        return 'invoice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Invoice::class,
                'currency' => $this->systemConfig->getCurrency()
            ]
        )
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
