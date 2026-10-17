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

use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;

/**
 * A single country row from Resources/config/jurisdictions.php, hydrated by JurisdictionRegistry.
 *
 * @see \SolidInvoice\EInvoiceBundle\Tests\Jurisdiction\JurisdictionRegistryTest
 */
final readonly class JurisdictionDefinition
{
    public function __construct(
        /**
         * ISO 3166-1 alpha-2, upper case.
         */
        public string $countryCode,
        /**
         * The syntax format this jurisdiction's documents are written in.
         */
        public SyntaxFormat $syntax,
        /**
         * The profile identifier to use when the caller does not pick one. Always a member of
         * $permittedProfiles.
         */
        public string $defaultProfile,
        /**
         * Profile identifiers permitted for this jurisdiction. Defaults to [$defaultProfile]
         * when the country only supports one profile.
         *
         * @var list<string>
         */
        public array $permittedProfiles,
        /**
         * BT codes that must be present for a valid invoice in this jurisdiction.
         *
         * @var list<string>
         */
        public array $mandatoryTerms,
        /**
         * Identifier scheme code (e.g. "eas") mapped to its value (e.g. "9958").
         *
         * @var array<string, string>
         */
        public array $identifierSchemes,
        /**
         * The channel identifier to transmit through when the caller does not pick one.
         */
        public string $defaultChannel,
    ) {
    }
}
