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

namespace SolidInvoice\EInvoiceBundle\Profile;

use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatableInterface;

#[AutoconfigureTag(ProfileInterface::DI_TAG)]
interface ProfileInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.profile';

    public function getIdentifier(): string;

    public function getCustomizationId(): string;

    public function getProfileId(): ?string;

    public function getSyntax(): SyntaxFormat;

    /**
     * @return list<string>
     */
    public function getMandatoryTerms(): array;

    /**
     * @return list<RuleSet>
     */
    public function getRuleSets(): array;

    public function getLabel(): TranslatableInterface;

    public function isValidVatInvoice(): bool;
}
