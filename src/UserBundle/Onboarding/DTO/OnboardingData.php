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

final class OnboardingData
{
    #[Assert\NotBlank]
    public ?string $companyName = null;

    #[Assert\NotBlank]
    #[Assert\Currency]
    public ?string $companyCurrency = 'USD';
}
