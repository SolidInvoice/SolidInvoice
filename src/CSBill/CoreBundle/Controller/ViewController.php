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

use Rhumsaa\Uuid\Uuid;

class ViewController extends BaseController
{
    /**
     * @var string
     */
    protected $repository;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var string
     */
    protected $route;

    /**
     * View a quote if not logged in
     *
     * @param  string                                     $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewquoteAction($uuid)
    {
        $this->repository = 'CSBillQuoteBundle:Quote';
        $this->route = '_quotes_view';
        $this->template = 'CSBillQuoteBundle::quote_template.html.twig';

        return $this->createResponse($uuid, 'quote');
    }

    /**
     * View a invoice if not logged in
     *
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewinvoiceAction($uuid)
    {
        $this->repository = 'CSBillInvoiceBundle:Invoice';
        $this->route = '_invoices_view';
        $this->template = 'CSBillInvoiceBundle::invoice_template.html.twig';

        return $this->createResponse($uuid, 'invoice');
    }

    /**
     * @param $uuid
     * @param $object
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function createResponse($uuid, $object)
    {
        $repository = $this->getRepository($this->repository);

        $entity = $repository->findOneBy(array('uuid' => Uuid::fromString($uuid)));

        if (null === $entity) {
            throw $this->createNotFoundException();
        }

        if (true === $this->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl($this->route, array('id' => $entity->getId())));
        }

        return $this->render($this->template, array($object => $entity));
    }
}
