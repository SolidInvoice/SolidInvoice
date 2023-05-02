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

namespace SolidInvoice\CoreBundle\Traits;

use Symfony\Contracts\Service\Attribute\Required;
use Doctrine\Persistence\ManagerRegistry;

trait DoctrineAwareTrait
{
    protected ?ManagerRegistry $doctrine = null;

    #[Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }
}
