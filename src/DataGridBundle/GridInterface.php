<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;

interface GridInterface
{
    /**
     * @return bool
     */
    public function requiresStatus(): bool;

    /**
     * @param Request       $request
     * @param ObjectManager $em
     *
     * @return mixed
     */
    public function fetchData(Request $request, ObjectManager $em): array;

    /**
     * @param array $params
     */
    public function setParameters(array $params);
}
