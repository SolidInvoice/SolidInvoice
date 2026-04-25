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

namespace SolidInvoice\CoreBundle\Company;

use SolidInvoice\CoreBundle\Entity\Company;

final class ResolvedHost
{
    public function __construct(
        public readonly HostType $type,
        public readonly string $host,
        public readonly string $scheme,
        public readonly int $port,
        public readonly ?Company $company = null,
    ) {
    }

    public function isCustomDomain(): bool
    {
        return $this->type === HostType::CustomDomain;
    }

    public function isDefaultHost(): bool
    {
        return $this->type === HostType::DefaultHost;
    }
}
