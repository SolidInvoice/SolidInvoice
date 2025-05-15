<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Event;

use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;

final class CompanyCreatedEvent extends Event
{
    public function __construct(
        public readonly Company $company
    ) {
    }
}
