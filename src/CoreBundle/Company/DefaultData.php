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

namespace SolidInvoice\CoreBundle\Company;

use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JsonException;
use Mpociot\VatCalculator\VatCalculator;
use RuntimeException;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_map;
use function array_unique;
use function get_debug_type;
use function round;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Company\DefaultDataTest
 */
final readonly class DefaultData
{
    /**
     * Countries the VatCalculator does not know, as country => [tax name key, percentages].
     *
     * ponytail: hand-maintained; update when one of these countries changes its rates.
     *
     * @var array<string, array{string, list<float>}>
     */
    private const array OTHER_TAX_RATES = [
        'AU' => ['tax.default_rate.gst', [10.0]],
        'GB' => ['tax.default_rate.vat', [20.0, 5.0]],
        'ZA' => ['tax.default_rate.vat', [15.0]],
    ];

    private ObjectManager $em;

    /**
     * @param iterable<ProviderInterface> $configProviders
     */
    public function __construct(
        ManagerRegistry $registry,
        #[AutowireIterator(ProviderInterface::class)]
        private iterable $configProviders,
        private VatCalculator $vatCalculator,
        private TranslatorInterface $translator,
    ) {
        $this->em = $registry->getManager();
    }

    /**
     * @param array{currency: string} $data
     * @throws JsonException
     */
    public function __invoke(Company $company, array $data): void
    {
        $this->createAppConfig($company, $data);
        $this->createDefaultCustomFields($company);
        $this->createPaymentMethods();
        $this->createTaxRates($company);

        $this->em->flush();
    }

    /**
     * One rate per distinct VAT/GST rate of the company's country: the EU, Switzerland, Monaco
     * and the Isle of Man from the VatCalculator, plus OTHER_TAX_RATES. Anywhere else (the US,
     * whose sales tax is set per state) starts with no rates.
     */
    private function createTaxRates(Company $company): void
    {
        $country = $company->getCountry();

        if ($country === null) {
            return;
        }

        if ($this->vatCalculator->shouldCollectVAT($country)) {
            $nameKey = 'tax.default_rate.vat';
            $percentages = array_map(static fn (float $rate): float => round($rate * 100, 2), $this->vatCalculator->getTaxRatesForCountry($country));
        } else {
            [$nameKey, $percentages] = self::OTHER_TAX_RATES[$country] ?? ['', []];
        }

        foreach (array_unique($percentages) as $percentage) {
            $tax = new Tax();
            $tax->setName($this->translator->trans($nameKey, ['%rate%' => $percentage]));
            $tax->setRate($percentage);
            $tax->setType(TaxType::Exclusive->value);
            $tax->setCompany($company);

            $this->em->persist($tax);
        }
    }

    private function createDefaultCustomFields(Company $company): void
    {
        $defaults = [
            // Phone and mobile are Contact columns, on every plan.
            ['additional_email', 'Additional Email', CustomFieldType::EMAIL, 0],
        ];

        foreach ($defaults as [$key, $label, $type, $position]) {
            $field = new CustomField()
                ->setTarget(CustomFieldTarget::CONTACT)
                ->setLabel($label)
                ->setFieldKey($key)
                ->setType($type)
                ->setPosition($position)
                ->setCompany($company);
            $this->em->persist($field);
        }
    }

    /**
     * @param array{currency: string} $data
     * @throws JsonException
     */
    private function createAppConfig(Company $company, array $data): void
    {
        foreach ($this->configProviders as $provider) {
            foreach ($provider->provide($data + ['company_name' => $company->getName()]) as $config) {
                if (! $config instanceof Config) {
                    throw new RuntimeException(sprintf('Config provider %s did not return an instance of %s. %s returned.', $provider::class, Config::class, get_debug_type($config)));
                }

                $settingEntity = new Setting();
                $settingEntity->setKey($config->key);
                $settingEntity->setValue($config->value);
                $settingEntity->setDescription($config->description);
                $settingEntity->setType($config->formType);
                $settingEntity->setFormOptions($config->formOptions);
                $settingEntity->setDefaultValue($config->value);
                $settingEntity->setCompany($company);

                $this->em->persist($settingEntity);
            }
        }
    }

    private function createPaymentMethods(): void
    {
        $paymentMethods = [
            [
                'name' => 'Cash',
                'gateway_name' => 'cash',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => 'Bank Transfer',
                'gateway_name' => 'bank_transfer',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => $this->translator->trans('payment.gateway.cheque.display_name'),
                'gateway_name' => 'cheque',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => $this->translator->trans('payment.gateway.card_terminal.display_name'),
                'gateway_name' => 'card_terminal',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
            [
                'name' => 'Credit',
                'gateway_name' => 'credit',
                'config' => [],
                'internal' => true,
                'enabled' => true,
                'factory' => 'offline',
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodEntity = new PaymentMethod();
            $paymentMethodEntity->setName($paymentMethod['name']);
            $paymentMethodEntity->setGatewayName($paymentMethod['gateway_name']);
            $paymentMethodEntity->setConfig($paymentMethod['config']);
            $paymentMethodEntity->setInternal($paymentMethod['internal']);
            $paymentMethodEntity->setEnabled($paymentMethod['enabled']);
            $paymentMethodEntity->setFactoryName($paymentMethod['factory']);
            $paymentMethodEntity->setCreated(Carbon::now());
            $paymentMethodEntity->setUpdated(Carbon::now());

            $this->em->persist($paymentMethodEntity);
        }
    }
}
