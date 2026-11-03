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

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use function htmlspecialchars;
use function strtolower;
use function strtoupper;

/**
 * The country a company trades from: names come from Intl in the request locale, each
 * prefixed with its Tabler flag, and a new company starts on the visitor's country.
 *
 * @extends AbstractType<string|null>
 * @see \SolidInvoice\CoreBundle\Tests\Form\Type\CompanyCountryTypeTest
 */
final class CompanyCountryType extends AbstractType
{
    // Set by Cloudflare when IP geolocation is on. Absent everywhere else (self-hosted).
    private const string COUNTRY_HEADER = 'CF-IPCountry';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            if ($event->getData() === null) {
                $event->setData($this->detectCountry());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'company.country.label',
            'placeholder' => 'company.country.placeholder',
            'help' => 'company.country.help',
            // The autocomplete renders the labels as HTML, and searches them with the tags stripped.
            'autocomplete' => true,
            'options_as_html' => true,
            'choice_label' => static fn (string $code, string $name): string => self::flag($code) . htmlspecialchars($name),
            'constraints' => [new NotBlank()],
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return CountryType::class;
    }

    public static function flag(string $code): string
    {
        return '<span class="flag flag-xs flag-country-' . strtolower($code) . ' me-2"></span>';
    }

    private function detectCountry(): ?string
    {
        $country = strtoupper((string) $this->requestStack->getCurrentRequest()?->headers->get(self::COUNTRY_HEADER));

        // Cloudflare sends XX when it cannot tell and T1 for Tor; neither is a country.
        return Countries::exists($country) ? $country : null;
    }
}
