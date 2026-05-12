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

namespace SolidInvoice\TaxBundle\Twig\Extension;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TaxBreakdownExtension extends AbstractExtension
{
    public function __construct(
        private readonly TaxIdentifierRepository $taxIdentifierRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tax_identifiers', $this->taxIdentifiers(...)),
        ];
    }

    /**
     * Returns an ordered list of TaxIdentifier entities for the given owner.
     *
     * - When $owner is a Client, returns its identifiers.
     * - When $owner is a Company (or null = active company), returns identifiers
     *   not bound to any client for that company.
     *
     * Primary identifier first, then ordered by label.
     *
     * @return list<TaxIdentifier>
     */
    public function taxIdentifiers(Client|Company|null $owner = null): array
    {
        $identifiers = $owner instanceof Client
            ? $this->forClient($owner)
            : $this->forCompany($owner);

        usort($identifiers, static function (TaxIdentifier $a, TaxIdentifier $b): int {
            if ($a->isPrimary() !== $b->isPrimary()) {
                return $a->isPrimary() ? -1 : 1;
            }

            return strcasecmp((string) $a->getLabel(), (string) $b->getLabel());
        });

        return $identifiers;
    }

    /**
     * @return list<TaxIdentifier>
     */
    private function forClient(Client $client): array
    {
        return array_values($client->getTaxIdentifiers()->toArray());
    }

    /**
     * @return list<TaxIdentifier>
     */
    private function forCompany(?Company $company): array
    {
        $companyId = $company?->getId() ?? $this->companySelector->getCompany();

        if ($companyId === null) {
            return [];
        }

        return $this->taxIdentifierRepository->findCompanyIdentifiers($companyId);
    }
}
