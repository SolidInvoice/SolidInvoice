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

use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use Money\Currency;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\ClientBundle\Form\ClientAutocompleteType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\InvoiceTypeTest
 */
class InvoiceType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly ManagerRegistry $registry,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|JsonException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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

        if (array_key_exists('data', $options) && $options['data'] instanceof Invoice) {
            $builder->addEventSubscriber(new InvoiceUsersSubscriber($builder, $options['data'], $this->registry));
        }
    }

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
