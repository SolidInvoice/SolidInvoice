<?php

/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Form\Client as ClientForm;
use CSBill\ClientBundle\Model\Status;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\DataGridBundle\Grid\GridCollection;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;

class GridController extends BaseController
{
    /**
     * Archives a list of clients
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function archiveAction(Request $request)
    {
	$data = $request->request->get('data');

	/** @var Client[] $clients */
	$clients = $this->getRepository('CSBillClientBundle:Client')->findBy(['id' => $data]);

	$em = $this->getEm();
	foreach ($clients as $client) {
	    $client->setArchived(true);
	    $em->persist($client);
	}

	$em->flush();

	return $this->json([]);
    }

    /**
     * Deletes a list of clients
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request)
    {
	$data = $request->request->get('data');

	/** @var Client[] $clients */
	$clients = $this->getRepository('CSBillClientBundle:Client')->findBy(['id' => $data]);

	$em = $this->getEm();
	foreach ($clients as $client) {
	    $em->remove($client);
	}

	$em->flush();

	return $this->json([]);
    }
}
