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

namespace SolidInvoice\EInvoiceBundle\Jurisdiction;

use InvalidArgumentException;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Exception\UnknownJurisdictionException;

/**
 * @see \SolidInvoice\EInvoiceBundle\Tests\Jurisdiction\JurisdictionRegistryTest
 */
final readonly class JurisdictionRegistry
{
    private const array ALLOWED_KEYS = [
        'syntax',
        'defaultProfile',
        'permittedProfiles',
        'mandatoryTerms',
        'identifierSchemes',
        'defaultChannel',
    ];

    private const array REQUIRED_KEYS = [
        'syntax',
        'defaultProfile',
        'mandatoryTerms',
        'identifierSchemes',
        'defaultChannel',
    ];

    /**
     * @var array<string, JurisdictionDefinition>
     */
    private array $jurisdictions;

    /**
     * @param array<string, array{
     *     syntax: SyntaxFormat,
     *     defaultProfile: string,
     *     permittedProfiles?: list<string>,
     *     mandatoryTerms: list<string>,
     *     identifierSchemes: array<string, string>,
     *     defaultChannel: string,
     * }> $rows
     *
     * @throws InvalidArgumentException when a row is malformed
     */
    public function __construct(array $rows)
    {
        $jurisdictions = [];

        foreach ($rows as $countryCode => $row) {
            $jurisdictions[self::normalize($countryCode)] = $this->hydrate($countryCode, $row);
        }

        $this->jurisdictions = $jurisdictions;
    }

    /**
     * @throws UnknownJurisdictionException
     */
    public function get(string $countryCode): JurisdictionDefinition
    {
        return $this->jurisdictions[self::normalize($countryCode)] ?? throw UnknownJurisdictionException::forCountryCode($countryCode);
    }

    public function has(string $countryCode): bool
    {
        return isset($this->jurisdictions[self::normalize($countryCode)]);
    }

    /**
     * @return array<string, JurisdictionDefinition>
     */
    public function all(): array
    {
        return $this->jurisdictions;
    }

    private static function normalize(string $countryCode): string
    {
        return strtoupper($countryCode);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(string $countryCode, array $row): JurisdictionDefinition
    {
        if (preg_match('/^[A-Za-z]{2}$/', $countryCode) !== 1) {
            throw new InvalidArgumentException(sprintf('Jurisdiction country code "%s" is not a valid ISO 3166-1 alpha-2 code.', $countryCode));
        }

        $unknownKeys = array_diff(array_keys($row), self::ALLOWED_KEYS);

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(sprintf('Jurisdiction row "%s" has unknown key(s): "%s".', $countryCode, implode('", "', $unknownKeys)));
        }

        $missingKeys = array_diff(self::REQUIRED_KEYS, array_keys($row));

        if ($missingKeys !== []) {
            throw new InvalidArgumentException(sprintf('Jurisdiction row "%s" is missing required key(s): "%s".', $countryCode, implode('", "', $missingKeys)));
        }

        /** @var SyntaxFormat $syntax */
        $syntax = $row['syntax'];
        /** @var string $defaultProfile */
        $defaultProfile = $row['defaultProfile'];
        /** @var list<string> $mandatoryTerms */
        $mandatoryTerms = $row['mandatoryTerms'];
        /** @var array<string, string> $identifierSchemes */
        $identifierSchemes = $row['identifierSchemes'];
        /** @var string $defaultChannel */
        $defaultChannel = $row['defaultChannel'];

        if (! array_is_list($mandatoryTerms)) {
            throw new InvalidArgumentException(sprintf('Jurisdiction row "%s"\'s "mandatoryTerms" must be a list.', $countryCode));
        }

        /** @var list<string> $permittedProfiles */
        $permittedProfiles = $row['permittedProfiles'] ?? [$defaultProfile];

        if (! array_is_list($permittedProfiles)) {
            throw new InvalidArgumentException(sprintf('Jurisdiction row "%s"\'s "permittedProfiles" must be a list.', $countryCode));
        }

        if (! in_array($defaultProfile, $permittedProfiles, true)) {
            throw new InvalidArgumentException(sprintf('Jurisdiction row "%s"\'s "defaultProfile" ("%s") must be a member of "permittedProfiles".', $countryCode, $defaultProfile));
        }

        return new JurisdictionDefinition(
            countryCode: self::normalize($countryCode),
            syntax: $syntax,
            defaultProfile: $defaultProfile,
            permittedProfiles: $permittedProfiles,
            mandatoryTerms: $mandatoryTerms,
            identifierSchemes: $identifierSchemes,
            defaultChannel: $defaultChannel,
        );
    }
}
