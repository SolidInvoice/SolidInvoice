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

namespace SolidInvoice\CoreBundle\Form\Type;

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function strtoupper;

/**
 * @extends AbstractType<Company>
 */
final class CompanyType extends AbstractType
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('country', CompanyCountryType::class)
            ->add(
                'currency',
                CurrencyType::class,
                [
                    'placeholder' => 'form.placeholder.choose_currency',
                ]
            );

        $builder->get('currency')->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            if ($event->getData() === null) {
                $country = $this->detectCountry();

                if ($country !== null) {
                    $currencies = Currencies::forCountry($country);

                    if (count($currencies) >= 1) {
                        $event->setData($currencies[0]);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }

    private function detectCountry(): ?string
    {
        $country = strtoupper((string) $this->requestStack->getCurrentRequest()?->headers->get(CompanyCountryType::COUNTRY_HEADER));

        // Cloudflare sends XX when it cannot tell and T1 for Tor; neither is a country.
        return Countries::exists($country) ? $country : null;
    }
}
