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

namespace CSBill\QuoteBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Exception\InvalidTransitionException;
use CSBill\QuoteBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GridController extends BaseController
{
    /**
     * Archives a list of quotes.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws InvalidTransitionException
     */
    public function archiveAction(Request $request): Response
    {
        $data = $request->request->get('data');

        /** @var Quote[] $quotes */
        $quotes = $this->getRepository('CSBillQuoteBundle:Quote')->findBy(['id' => $data]);

        $quoteManager = $this->get('quote.manager');

        $em = $this->getEm();
        foreach ($quotes as $quote) {
            if (!$this->get('finite.factory')->get($quote, Graph::GRAPH)->can('archive')) {
                throw new InvalidTransitionException('archive');
            }

            $quoteManager->archive($quote);
        }

        $em->flush();

        return $this->json([]);
    }

    /**
     * Deletes a list of quotes.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction(Request $request): Response
    {
        $data = $request->request->get('data');

    /* @var Quote[] $quote */
    $quotes = $this->getRepository('CSBillQuoteBundle:Quote')->findBy(['id' => $data]);

        $em = $this->getEm();
        foreach ($quotes as $quote) {
            $em->remove($quote);
        }

        $em->flush();

        return $this->json([]);
    }
}
