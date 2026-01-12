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

namespace SolidInvoice\UserBundle\Onboarding\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Main DTO for onboarding flow
 */
final class OnboardingData
{
    public ?string $currentStep = null;

    // Step 1: Company
    #[Assert\NotBlank(groups: ['company'])]
    public ?string $companyName = null;

    #[Assert\NotBlank(groups: ['company'])]
    #[Assert\Currency(groups: ['company'])]
    public ?string $companyCurrency = 'USD';

    // Step 2: Client (optional)
    #[Assert\NotBlank(groups: ['client'])]
    public ?string $clientName = null;

    #[Assert\NotBlank(groups: ['client'])]
    #[Assert\Email(groups: ['client'])]
    public ?string $clientEmail = null;

    // Step 3: Invoice (optional)
    #[Assert\NotBlank(groups: ['invoice'])]
    public ?string $invoiceDescription = null;

    #[Assert\NotBlank(groups: ['invoice'])]
    #[Assert\Positive(groups: ['invoice'])]
    public ?string $invoiceAmount = null;
}
