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

namespace SolidInvoice\ClientBundle\Form\Type;

use Override;
use RuntimeException;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Form\Type\TaxIdentifierType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\ClientTypeTest
 * @extends AbstractType<Client>
 */
class ClientType extends AbstractType
{
    public function __construct(
        private readonly FeatureGate $featureGate,
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, ['sanitize_html' => true, 'allow_single_quotes' => true]);
        $builder->add('website', UrlType::class, ['required' => false]);

        if ($this->featureGate->isEnabled(Feature::MultiCurrency->value)) {
            $builder->add(
                'currencyCode',
                CurrencyType::class,
                [
                    'placeholder' => 'client.form.currency.empty_value',
                    'required' => false,
                ]
            );
        } else {
            $defaultCurrency = $this->resolveDefaultCurrencyCode();

            $builder->add(
                'currencyCode',
                CurrencyType::class,
                [
                    'placeholder' => 'client.form.currency.empty_value',
                    'required' => false,
                    'disabled' => true,
                    'data' => $defaultCurrency,
                    'feature_gated' => Feature::MultiCurrency->value,
                ]
            );

            $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event) use ($defaultCurrency): void {
                $client = $event->getData();

                // Only override when a concrete default currency is available — passing null
                // would clear the existing currency on the entity when editing.
                if ($client instanceof Client && null !== $defaultCurrency) {
                    $client->setCurrencyCode($defaultCurrency);
                }
            });
        }

        $builder->add(
            'contacts',
            LiveCollectionType::class,
            [
                'entry_type' => ContactType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'button_delete_options' => [
                    'label_html' => true,
                ],
            ]
        );

        $builder->add(
            'addresses',
            LiveCollectionType::class,
            [
                'entry_type' => AddressType::class,
                'entry_options' => [
                    'data_class' => Address::class,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ]
        );

        $builder->add(
            'taxIdentifiers',
            LiveCollectionType::class,
            [
                'entry_type' => TaxIdentifierType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ]
        );

        if ($this->featureGate->isEnabled(Feature::CustomFields->value)) {
            $builder->add('customFields', CustomFieldValueCollectionType::class, [
                'target' => CustomFieldTarget::CLIENT,
                'parent_record' => $options['data'] ?? null,
                'manage_persistence' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'validation_groups' => ['Default', 'form'],
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'client';
    }

    private function resolveDefaultCurrencyCode(): ?string
    {
        try {
            return $this->systemConfig->getCurrency()->getCode();
        } catch (RuntimeException) {
            return null;
        }
    }
}
