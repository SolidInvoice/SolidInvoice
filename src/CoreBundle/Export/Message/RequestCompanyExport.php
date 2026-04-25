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

namespace SolidInvoice\CoreBundle\Export\Message;

use Symfony\Component\Uid\Ulid;

final readonly class RequestCompanyExport
{
    public function __construct(
        public Ulid $exportJobId,
        public Ulid $companyId,
        public Ulid $userId,
    ) {
    }
}
