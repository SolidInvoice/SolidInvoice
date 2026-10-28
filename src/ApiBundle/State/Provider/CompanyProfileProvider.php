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

namespace SolidInvoice\ApiBundle\State\Provider;

use const JSON_THROW_ON_ERROR;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use JsonException;
use SolidInvoice\ApiBundle\ApiResource\CompanyProfile;
use SolidInvoice\CoreBundle\Company\CompanyLogo;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use function array_map;
use function is_array;
use function json_decode;

/**
 * @implements ProviderInterface<CompanyProfile>
 */
final readonly class CompanyProfileProvider implements ProviderInterface
{
    public const string EMAIL = 'system/company/contact_details/email';

    public const string PHONE = 'system/company/contact_details/phone_number';

    public const string ADDRESS = 'system/company/contact_details/address';

    public function __construct(
        private CompanySelector $companySelector,
        private CompanyRepository $companyRepository,
        private SystemConfig $config,
        private TaxIdentifierRepository $taxIdentifierRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CompanyProfile
    {
        $companyId = $this->companySelector->getCompany();
        $company = $companyId instanceof Ulid ? $this->companyRepository->find($companyId) : null;

        if (! $company instanceof Company) {
            throw new NotFoundHttpException();
        }

        $profile = new CompanyProfile();
        $profile->name = $company->getName();
        $profile->email = $this->setting(self::EMAIL);
        $profile->phone = $this->setting(self::PHONE);

        $address = $this->setting(self::ADDRESS);
        $address = $address === null ? null : json_decode($address, true, flags: JSON_THROW_ON_ERROR);

        $profile->address = is_array($address) ? $address : null;
        $profile->country = $company->getCountry();
        $profile->currency = $this->setting(SystemConfig::CURRENCY_CONFIG_PATH);
        $profile->logoUrl = $this->setting(CompanyLogo::SETTING) === null
            ? null
            : $this->urlGenerator->generate('api_company_logo_show', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $profile->taxIdentifiers = array_map(
            static fn (TaxIdentifier $identifier): array => [
                'label' => $identifier->getLabel(),
                'value' => $identifier->getValue(),
                'primary' => $identifier->isPrimary(),
            ],
            $this->taxIdentifierRepository->findCompanyIdentifiers($company->getId()),
        );

        return $profile;
    }

    private function setting(string $key): ?string
    {
        $value = $this->config->get($key);

        return $value === '' ? null : $value;
    }
}
