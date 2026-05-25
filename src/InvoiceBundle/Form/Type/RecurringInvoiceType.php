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

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Override;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CronBundle\Form\Type\RecurringScheduleType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Form\Type\InvoiceTaxType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
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
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\RecurringInvoiceTypeTest
 * @extends AbstractType<RecurringInvoice>
 */
class RecurringInvoiceType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly ManagerRegistry $registry,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add(
            'client',
            null,
            [
                'attr' => [
                    'class' => 'client-select',
                ],
                'placeholder' => 'invoice.client.choose',
                'choices' => $this->registry->getRepository(Client::class)->findAll(),
            ]
        );

        $builder->add('discount', DiscountType::class, ['required' => false, 'label' => 'Discount', 'currency' => $options['currency']]);

        $builder->add(
            'lines',
            LiveCollectionType::class,
            [
                'entry_type' => RecurringInvoiceLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'entry_options' => [
                    'currency' => $options['currency'],
                ],
            ]
        );

        $builder->add(
            'invoiceTaxes',
            LiveCollectionType::class,
            [
                'entry_type' => InvoiceTaxType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'by_reference' => false,
                'label' => 'Withholding & adjustments',
                'attr' => [
                    'data-controller' => 'invoice-tax',
                ],
            ]
        );

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);

        $builder->addDependent('users', 'client', function (DependentField $field, ?Client $client): void {
            if (! $client instanceof Client) {
                return;
            }

            $clientId = $client->getId();
            $field->add(
                null,
                [
                    'constraints' => new NotBlank(),
                    'expanded' => true,
                    'multiple' => true,
                    'query_builder' => fn (EntityRepository $repo) => $repo->createQueryBuilder('c')
                        ->where('c.client = :client')
                        ->setParameter('client', $clientId, UlidType::NAME),
                ]
            );
        });

        $builder->add('recurringOptions', RecurringScheduleType::class);

        $now = CarbonImmutable::now();

        $builder->add(
            'date_start',
            DateType::class,
            [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $now,
                'label' => 'invoice.recurring.date_start',
                'attr' => [
                    'class' => 'datepicker',
                    'data-min-date' => $now->format('Y-m-d'),
                ],
            ]
        );

        if ($this->featureGate->isEnabled(Feature::CustomFields->value)) {
            $builder->add('customFields', CustomFieldValueCollectionType::class, [
                'target' => CustomFieldTarget::INVOICE,
                'parent_record' => $options['data'] ?? null,
            ]);
        }
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'recurring_invoice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(
                [
                    'data_class' => RecurringInvoice::class,
                    'currency' => $this->systemConfig->getCurrency(),
                ]
            )
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
