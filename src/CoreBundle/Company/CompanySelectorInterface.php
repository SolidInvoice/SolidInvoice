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

use Symfony\Component\Uid\Ulid;

interface CompanySelectorInterface
{
    public function getCompany(): ?Ulid;

    /**
     * Scopes everything that follows (Doctrine's company filter, settings, URLs) to this company.
     */
    public function switchCompany(Ulid $companyId): void;
}
