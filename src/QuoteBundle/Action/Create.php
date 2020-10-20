<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Action;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteCreateHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

final class Create
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
        $totalClientsCount = $this->repository->getTotalClients();
        if ($totalClientsCount === 0) {
            return new Template('@SolidInvoiceQuote/Default/empty_clients.html.twig');
        } elseif (1 === $totalClientsCount && is_null($client)) {
            $client = $this->repository->findOneBy([]);
        }
        if (1 === $totalClientsCount && null === $client) {
            $client = $this->repository->findOneBy([]);
        }

        $quote = new Quote();
        $quote->setClient($client);

        $options = [
            'quote' => $quote,
            'form_options' => ($client && $currency = $client->getCurrency()) ? ['currency' => $currency] : [],
        ];

        return $this->handler->handle(QuoteCreateHandler::class, $options);
    }
}
