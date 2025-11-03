<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Config;

use SolidInvoice\SettingsBundle\DTO\Config;

interface ProviderInterface
{
    /**
     * @param array{company_name?: string|null, currency?: string|null} $data
     *
     * @return Config[]
     */
    public function provide(array $data): array;
}
