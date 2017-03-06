<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class DataController extends BaseController
{
    /**
     * @param Request $request
     * @param string  $name
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \CSBill\DataGridBundle\Exception\InvalidGridException
     */
    public function getDataAction(Request $request, $name)
    {
	$grid = $this->get('grid.repository')->find($name);

	$grid->setParameters($request->get('parameters', []));

	return $this->serializeJs($grid->fetchData($request, $this->getEm()));
    }
}
