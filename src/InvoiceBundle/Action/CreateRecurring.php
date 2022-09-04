<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceCreateHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

final class CreateRecurring
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
        $totalClientsCount = $this->clientRepository->getTotalClients();
        if ($totalClientsCount === 0) {
            return new Template('@SolidInvoiceInvoice/Default/empty_clients.html.twig');
        }
        if (1 === $totalClientsCount && null === $client) {
            $client = $this->clientRepository->findOneBy([]);
        }

        $invoice = new RecurringInvoice();
        $invoice->setClient($client);

        $options = [
            'invoice' => $invoice,
            'form_options' => ($client && $currency = $client->getCurrency()) ? ['currency' => $currency] : [],
            'recurring' => true,
        ];

        return $this->handler->handle(InvoiceCreateHandler::class, $options);
    }
}
