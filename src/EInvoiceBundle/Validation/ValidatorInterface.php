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

namespace SolidInvoice\EInvoiceBundle\Validation;

use SolidInvoice\EInvoiceBundle\Enum\ValidationStage;
use SolidInvoice\EInvoiceBundle\Profile\ProfileInterface;
use SolidInvoice\EInvoiceBundle\Syntax\SyntaxDocument;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(ValidatorInterface::DI_TAG)]
interface ValidatorInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.validator';

    public function supports(ProfileInterface $profile, ValidationStage $stage): bool;

    public function validate(SyntaxDocument $document, ProfileInterface $profile): ValidationReport;
}
