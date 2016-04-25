<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

interface GridInterface
{
    /**
     * @return bool
     */
    public function requiresStatus();

    /**
     * @param Request                $request
     * @param EntityManagerInterface $em
     *
     * @return mixed
     */
    public function fetchData(Request $request, EntityManagerInterface $em);

    /**
     * @param array $params
     */
    public function setParameters(array $params);
}
