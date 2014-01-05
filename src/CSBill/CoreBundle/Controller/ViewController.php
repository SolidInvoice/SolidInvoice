<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\Controller;

use CS\CoreBundle\Controller\Controller as BaseController;
use Rhumsaa\Uuid\Uuid;

class ViewController extends BaseController
{
    /**
     * View a quote/invoice if not logged in
     */
    public function viewAction($type, $uuid)
    {
        switch ($type) {
            case 'quote':
                $repository = $this->getRepository('CSBillQuoteBundle:Quote');
                $route = '_quotes_view';
                $template = 'CSBillQuoteBundle::quote_template.html.twig';
                break;

            case 'invoice':
                $repository = $this->getRepository('CSBillInvoiceBundle:Invoice');
                $route = '_invoices_view';
                $template = 'CSBillInvoiceBundle::invoice_template.html.twig';
                break;

            default:
                throw $this->createNotFoundException();
        }

        $entity = $repository->findOneBy(array('uuid' => Uuid::fromString($uuid)));

        if (null === $entity) {
            throw $this->createNotFoundException();
        }

        $security = $this->get('security.context');

        if (true === $security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl($route, array('id' => $entity->getId())));
        }

        return $this->render($template, array($type => $entity));
    }
}
