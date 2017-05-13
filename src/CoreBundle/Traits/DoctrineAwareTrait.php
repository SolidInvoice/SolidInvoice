<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Traits;

use Doctrine\Common\Persistence\ManagerRegistry;

trait DoctrineAwareTrait
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     *
     * @required
     */
    public function setDoctrine(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
}
