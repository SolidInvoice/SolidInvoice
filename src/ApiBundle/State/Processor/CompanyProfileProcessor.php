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

namespace SolidInvoice\ApiBundle\State\Processor;

use const JSON_THROW_ON_ERROR;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ApiBundle\ApiResource\CompanyProfile;
use SolidInvoice\ApiBundle\State\Provider\CompanyProfileProvider;
use SolidInvoice\CoreBundle\Company\CompanyLogo;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use Throwable;
use function json_encode;

/**
 * Saves the profile (PATCH /company), or removes the logo (DELETE /company/logo).
 *
 * @implements ProcessorInterface<CompanyProfile, CompanyProfile|null>
 */
final readonly class CompanyProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private SettingsRepository $settings,
        private CompanyLogo $logo,
        private CompanySelector $companySelector,
        private EntityManagerInterface $entityManager,
        private CompanyProfileProvider $provider,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?CompanyProfile
    {
        if ($operation instanceof DeleteOperationInterface) {
            $this->logo->remove();

            return null;
        }

        // The company name setting also renames the company (SettingsRepository::store()).
        $this->settings->store([
            'system/company/company_name' => $data->name,
            CompanyProfileProvider::EMAIL => $data->email,
            CompanyProfileProvider::PHONE => $data->phone,
            CompanyProfileProvider::ADDRESS => $data->address === null ? null : json_encode($data->address, JSON_THROW_ON_ERROR),
            SystemConfig::CURRENCY_CONFIG_PATH => $data->currency,
        ]);

        $company = $this->entityManager->find(Company::class, $this->companySelector->getCompany());

        if ($company instanceof Company && $company->getCountry() !== $data->country) {
            $company->setCountry($data->country);
            $this->entityManager->flush();
        }

        return $this->provider->provide($operation);
    }
}
