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

final readonly class ValidatorRegistry
{
    /**
     * @param iterable<ValidatorInterface> $validators
     */
    public function __construct(
        private iterable $validators
    ) {
    }

    /**
     * @return list<ValidatorInterface>
     */
    public function all(): array
    {
        return is_array($this->validators) ? array_values($this->validators) : iterator_to_array($this->validators, false);
    }

    /**
     * @return list<ValidatorInterface>
     */
    public function supporting(ProfileInterface $profile, ValidationStage $stage): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (ValidatorInterface $validator): bool => $validator->supports($profile, $stage),
        ));
    }
}
