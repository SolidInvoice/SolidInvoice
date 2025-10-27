<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\DTO;

final class Config
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly ?string $description,
        public readonly ?string $formType,
    ) {
    }
}
