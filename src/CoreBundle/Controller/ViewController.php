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

namespace CSBill\CoreBundle\Controller;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

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
     * View a quote if not logged in.
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function viewquoteAction(string $uuid): Response
    {
        $this->repository = 'CSBillQuoteBundle:Quote';
        $this->route = '_quotes_view';
        $this->template = 'CSBillQuoteBundle::quote_template.html.twig';

        return $this->createResponse($uuid, 'quote');
    }

    /**
     * View a invoice if not logged in.
     *
     * @param $uuid
     *
     * @return Response
     */
    public function viewinvoiceAction($uuid): Response
    {
        $this->repository = 'CSBillInvoiceBundle:Invoice';
        $this->route = '_invoices_view';
        $this->template = 'CSBillInvoiceBundle::invoice_template.html.twig';

        return $this->createResponse($uuid, 'invoice');
    }

    /**
     * @param $uuid
     * @param string $object
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function createResponse($uuid, string $object): Response
    {
        $repository = $this->getRepository($this->repository);

        $entity = $repository->findOneBy(['uuid' => Uuid::fromString($uuid)]);

        if (null === $entity) {
            throw $this->createNotFoundException(sprintf('"%s" with id %s does not exist', $object, $uuid));
        }

        if (true === $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl($this->route, ['id' => $entity->getId()]));
        }

        $template = 'CSBillCoreBundle:View:'.$object.'.html.twig';

        return $this->render(
            $template,
            [
                $object => $entity,
                'title' => $object.' #'.$entity->getId(),
                'template' => $this->template,
            ]
        );
    }
}
