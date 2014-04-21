<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Controller;

class DashboardController extends BaseController
{
    /**
     * Homepage action
     */
    public function indexAction()
    {
        return $this->render('CSBillCoreBundle:Dashboard:index.html.twig');
    }
}
