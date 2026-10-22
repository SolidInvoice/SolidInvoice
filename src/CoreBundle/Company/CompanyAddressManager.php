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

use const JSON_THROW_ON_ERROR;
use JsonException;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Repository\AddressRepository;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Ulid;
use function is_array;
use function json_decode;

/**
 * Reads and writes the company's postal address.
 *
 * The address lives on an `Address` row with no client (`CompanyAddressManager::get()`
 * relies on `AddressRepository::findCompanyAddress()` for the `client IS NULL` guard).
 * Until that row exists, reads fall back to the legacy
 * `system/company/contact_details/address` setting, which `store()` still mirrors.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Company\CompanyAddressManagerTest
 */
final class CompanyAddressManager
{
    private const string SETTING_KEY = 'system/company/contact_details/address';

    private bool $resolved = false;

    private ?Ulid $resolvedCompanyId = null;

    private ?Address $address = null;

    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly CompanySelector $companySelector,
        // Lazy: SettingsRepository depends on this manager, and SystemConfig depends on
        // SettingsRepository, so an eager reference here is a circular service graph.
        // SystemConfig is only touched on the legacy-fallback path in fromLegacySetting().
        #[Autowire(lazy: true)]
        private readonly SystemConfig $systemConfig,
    ) {
    }

    /**
     * Returns the company's postal address, or null if none is set.
     *
     * When no `Address` row exists yet, this falls back to the legacy setting and
     * returns a transient `Address` that is never persisted or added to the identity
     * map. Callers must treat it as read-only.
     *
     * The result is cached per selected company, so a loop that switches company
     * mid-request still resolves each company's own address. The cache is only
     * written after a successful resolution, so a transient failure (e.g. a DB
     * error) does not poison the cache and is retried on the next call.
     */
    public function get(): ?Address
    {
        $companyId = $this->companySelector->getCompany();

        if ($this->resolved && $this->isSameCompany($companyId)) {
            return $this->address;
        }

        if (! $companyId instanceof Ulid) {
            $this->resolvedCompanyId = null;
            $this->resolved = true;

            return $this->address = null;
        }

        $address = $this->addressRepository->findCompanyAddress($companyId);
        $resolvedAddress = $address instanceof Address ? $address : $this->fromLegacySetting();

        $this->address = $resolvedAddress;
        $this->resolvedCompanyId = $companyId;
        $this->resolved = true;

        return $resolvedAddress;
    }

    private function isSameCompany(?Ulid $companyId): bool
    {
        if (! $companyId instanceof Ulid) {
            return ! $this->resolvedCompanyId instanceof Ulid;
        }

        return $companyId->equals($this->resolvedCompanyId);
    }

    /**
     * Mirrors the settings form's address field onto the `Address` row, keeping
     * at most one per company by updating the existing row rather than appending.
     *
     * @param ?string $settingValue the raw JSON the settings form produced
     */
    public function store(?string $settingValue): void
    {
        $this->resolved = false;
        $this->resolvedCompanyId = null;
        $this->address = null;

        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return;
        }

        if (null === $settingValue || '' === $settingValue) {
            $this->removeExisting($companyId);

            return;
        }

        $decoded = $this->decode($settingValue);

        if (! is_array($decoded)) {
            // Malformed JSON or a value that is not a JSON object: leave whatever
            // is already stored untouched rather than guessing at the intent.
            return;
        }

        $address = $this->addressRepository->findCompanyAddress($companyId);

        if (! $address instanceof Address) {
            $company = $this->companyRepository->find($companyId);

            if (! $company instanceof Company) {
                return;
            }

            $address = new Address();
            $address->setCompany($company);
        }

        $address
            ->setStreet1($decoded['street1'] ?? null)
            ->setStreet2($decoded['street2'] ?? null)
            ->setCity($decoded['city'] ?? null)
            ->setState($decoded['state'] ?? null)
            ->setZip($decoded['zip'] ?? null)
            ->setCountry($decoded['country'] ?? null);

        $this->addressRepository->save($address);
    }

    private function removeExisting(Ulid $companyId): void
    {
        $address = $this->addressRepository->findCompanyAddress($companyId);

        if ($address instanceof Address) {
            $this->addressRepository->remove($address);
        }
    }

    private function fromLegacySetting(): ?Address
    {
        $settingValue = $this->systemConfig->get(self::SETTING_KEY);

        if (null === $settingValue || '' === $settingValue) {
            return null;
        }

        $decoded = $this->decode($settingValue);

        if (! is_array($decoded) || [] === $decoded) {
            return null;
        }

        return Address::fromArray($decoded);
    }

    /**
     * @return array<string, mixed>|null null on malformed JSON or a non-object value
     */
    private function decode(string $value): ?array
    {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
