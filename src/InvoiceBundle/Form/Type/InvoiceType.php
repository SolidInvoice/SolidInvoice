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
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\ClientAutocompleteType;
use SolidInvoice\CoreBundle\Form\Type\DiscountType;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceClientMode;
use SolidInvoice\MoneyBundle\Form\Type\HiddenMoneyType;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Form\Type\InvoiceTypeTest
 */
class InvoiceType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|JsonException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add(
            'clientMode',
            EnumType::class,
            [
                'class' => InvoiceClientMode::class,
                'expanded' => true,
                'label' => false,
                'choice_attr' => fn () => ['data-action' => 'live#$render'],
            ]
        );

        // Existing client selection (mode='existing')
        $builder->addDependent('client', 'clientMode', function (DependentField $field, ?InvoiceClientMode $mode): void {
            if ($mode === InvoiceClientMode::Existing) {
                $field->add(
                    ClientAutocompleteType::class,
                    [
                        'placeholder' => 'invoice.client.choose',
                    ]
                );
            }
        });

        // Inline client fields (mode='new')
        $builder->addDependent('newClientName', 'clientMode', function (DependentField $field, ?InvoiceClientMode $mode): void {
            if ($mode === InvoiceClientMode::NewClient) {
                $field->add(
                    TextType::class,
                    [
                        'label' => 'client.name',
                        'allow_single_quotes' => true,
                    ]
                );
            }
        });

        $builder->addDependent('newContactFirstName', 'clientMode', function (DependentField $field, ?InvoiceClientMode $mode): void {
            if ($mode === InvoiceClientMode::NewClient) {
                $field->add(
                    TextType::class,
                    [
                        'label' => 'contact.firstName',
                        'allow_single_quotes' => true,
                    ]
                );
            }
        });

        $builder->addDependent('newContactLastName', 'clientMode', function (DependentField $field, ?InvoiceClientMode $mode): void {
            if ($mode === InvoiceClientMode::NewClient) {
                $field->add(
                    TextType::class,
                    [
                        'label' => 'contact.lastName',
                        'required' => false,
                        'allow_single_quotes' => true,
                    ]
                );
            }
        });

        $builder->addDependent('newContactEmail', 'clientMode', function (DependentField $field, ?InvoiceClientMode $mode): void {
            if ($mode === InvoiceClientMode::NewClient) {
                $field->add(
                    EmailType::class,
                    [
                        'label' => 'contact.email',
                    ]
                );
            }
        });

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

        $dto = $options['data'] ?? new InvoiceFormDTO();

        // Generate invoice ID if not set (for new invoices)
        $data = $dto->invoiceId !== '' ? $dto->invoiceId : $this->billingIdGenerator->generate(new Invoice(), ['field' => 'invoiceId']);

        $builder->add('invoiceId', null, ['data' => $data]);

        $builder->add('terms');
        $builder->add('notes', null, ['help' => 'Notes will not be visible to the client']);
        $builder->add('total', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('baseTotal', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('tax', HiddenMoneyType::class, ['currency' => $options['currency']]);
        $builder->add('invoiceDate', DateType::class, ['widget' => 'single_text', 'input' => 'datetime_immutable']);
        $builder->add('due', DateType::class, ['widget' => 'single_text', 'label' => 'Due Date', 'required' => false, 'input' => 'datetime_immutable']);

        $builder->addDependent('users', 'client', function (DependentField $field, ?Client $client): void {
            if (! $client instanceof Client || ! $client->getId() instanceof Ulid) {
                return;
            }

            $clientId = $client->getId();
            $field->add(
                EntityType::class,
                [
                    'class' => Contact::class,
                    'constraints' => new NotBlank(),
                    'expanded' => true,
                    'multiple' => true,
                    'query_builder' => function (EntityRepository $repo) use ($clientId) {
                        return $repo->createQueryBuilder('c')
                            ->where('c.client = :client')
                            ->setParameter('client', $clientId, UlidType::NAME);
                    },
                ]
            );
        });
    }

    public function getBlockPrefix(): string
    {
        return 'invoice';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => InvoiceFormDTO::class,
                'currency' => $this->systemConfig->getCurrency(),
                'validation_groups' => function (FormInterface $form) {
                    $data = $form->getData();
                    $groups = ['Default'];
                    if ($data instanceof InvoiceFormDTO) {
                        if ($data->clientMode === InvoiceClientMode::NewClient) {
                            $groups[] = 'new_client';
                        } else {
                            $groups[] = 'existing_client';
                        }
                    }
                    return $groups;
                },
            ]
        )
            ->setAllowedTypes('currency', [Currency::class]);
    }
}
