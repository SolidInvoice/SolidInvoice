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

namespace CSBill\InvoiceBundle\Action;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\CoreBundle\Templating\Template;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Form\Handler\InvoiceCreateHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

final class Create
{
    /**
     * @var FormHandler
     */
    private $handler;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    public function __construct(FormHandler $handler, ClientRepository $clientRepository)
    {
        $this->handler = $handler;
        $this->clientRepository = $clientRepository;
    }

    public function __invoke(Request $request, Client $client = null)
    {
        if (!$this->clientRepository->getTotalClients()) {
            return new Template('@CSBillInvoice/Default/empty_clients.html.twig');
        }

        return $this->handler->handle(InvoiceCreateHandler::class, new Invoice($client), ($client && $currency = $client->getCurrency()) ? ['currency' => $currency] : null);
    }
}
