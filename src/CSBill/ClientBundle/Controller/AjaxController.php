<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use CSBill\ClientBundle\Entity\Client;

class AjaxController extends Controller
{
    /**
     * Get client info
     *
     * @param Client $client
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(Client $client)
    {
        return $this->render('CSBillClientBundle:Ajax:info.html.twig', array('client' => $client));
    }
}
