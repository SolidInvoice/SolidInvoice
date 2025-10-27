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

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JsonException;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\Entity\Company;
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
        $this->createContactTypes();
        $this->createPaymentMethods();

        $this->em->flush();
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
                    throw new \RuntimeException(sprintf('Config provider %s did not return an instance of %s. %s returned.', $provider::class, Config::class, get_debug_type($config)));
                }

                $settingEntity = new Setting();
                $settingEntity->setKey($config->key);
                $settingEntity->setValue($config->value);
                $settingEntity->setDescription($config->description);
                $settingEntity->setType($config->formType);

                $this->em->persist($settingEntity);
            }
        }
    }

    private function createContactTypes(): void
    {
        $contactTypes = [
            [
                'name' => 'email',
                'required' => true,
                'type' => 'email',
                'field_options' => [
                    'constraints' => ['email'],
                ],
            ],
            [
                'name' => 'mobile',
                'required' => false,
                'type' => 'text',
                'field_options' => []
            ],
            [
                'name' => 'phone',
                'required' => false,
                'type' => 'text',
                'field_options' => []
            ],
        ];

        foreach ($contactTypes as $contactType) {
            $contactTypeEntity = new ContactType();
            $contactTypeEntity->setName($contactType['name']);
            $contactTypeEntity->setRequired($contactType['required']);
            $contactTypeEntity->setType($contactType['type']);
            $contactTypeEntity->setOptions($contactType['field_options']);

            $this->em->persist($contactTypeEntity);
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
            $paymentMethodEntity->setCreated(new DateTime());
            $paymentMethodEntity->setUpdated(new DateTime());

            $this->em->persist($paymentMethodEntity);
        }
    }
}
