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

namespace CSBill\QuoteBundle\Action;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\CoreBundle\Templating\Template;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Form\Handler\QuoteCreateHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

class Create
{
    /**
     * @var ClientRepository
     */
    private $repository;

    /**
     * @var FormHandler
     */
    private $handler;

    public function __construct(ClientRepository $repository, FormHandler $handler)
    {
        $this->repository = $repository;
        $this->handler = $handler;
    }

    public function __invoke(Request $request, Client $client = null)
    {
        if (!$this->repository->getTotalClients()) {
            return new Template('@CSBillQuote/Default/empty_clients.html.twig');
        }

        return $this->handler->handle(QuoteCreateHandler::class, new Quote($client), ($client && $currency = $client->getCurrency()) ? ['currency' => $currency] : null);
    }
}
