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

/*
 * One row per ISO 3166-1 alpha-2 country code, hydrated by
 * SolidInvoice\EInvoiceBundle\Jurisdiction\JurisdictionRegistry. Adding a country's rules must
 * not require adding a class (ADR §10.3): a row and a Profile are enough.
 *
 * permittedProfiles defaults to [defaultProfile] when omitted. defaultProfile must be a member
 * of permittedProfiles, or the registry throws at build time.
 *
 * This is the ADR's worked example. It stays commented out until src/EInvoiceBundle/Profile/
 * ships an "xrechnung-3.0.2" profile (SOL-93), so ProfileRegistry::get() never has to throw for
 * a jurisdiction row that points at a profile nobody has written yet.
 *
 * 'DE' => [
 *     'syntax'            => SyntaxFormat::Cii,
 *     'defaultProfile'    => 'xrechnung-3.0.2',
 *     'permittedProfiles' => ['xrechnung-3.0.2'],
 *     'mandatoryTerms'    => ['BT-10'],
 *     'identifierSchemes' => ['eas' => '9958'],
 *     'defaultChannel'    => 'peppol',
 * ],
 */
return [];
