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
use RuntimeException;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function get_debug_type;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Company\DefaultDataTest
 */
final readonly class DefaultData
{
    private ObjectManager $em;

    /**
     * @param iterable<ProviderInterface> $configProviders
     */
    public function __construct(
        ManagerRegistry $registry,
        #[AutowireIterator(ProviderInterface::class)]
        private iterable $configProviders,
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

        $this->em->flush();
    }

    private function createDefaultCustomFields(Company $company): void
    {
        $defaults = [
            ['additional_email', 'Additional Email', CustomFieldType::EMAIL, 0],
            ['phone', 'Phone', CustomFieldType::TEXT, 1],
            ['mobile', 'Mobile', CustomFieldType::TEXT, 2],
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
