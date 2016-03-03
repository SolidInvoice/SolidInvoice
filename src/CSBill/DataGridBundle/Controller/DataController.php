<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;

class DataController extends BaseController
{
    /**
     * @param string $name
     */
    public function getDataAction($name)
    {
	$grid = $this->get('grid.repository')->find($name);

	return $this->serializeJs($grid->fetchData($this->getEm()));
    }
}