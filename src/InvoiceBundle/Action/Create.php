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
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Form\Handler\InvoiceCreateHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;

final class Create
{
    public function __construct(
        private readonly FormHandler $handler,
        private readonly ClientRepository $clientRepository
    ) {
    }

    public function __invoke(Request $request, Client $client = null): FormRequest | Template
    {
        $totalClientsCount = $this->clientRepository->getTotalClients();
        if (0 === $totalClientsCount) {
            return new Template('@SolidInvoiceInvoice/Default/empty_clients.html.twig');
        }
        if (1 === $totalClientsCount && ! $client instanceof Client) {
            $client = $this->clientRepository->findOneBy([]);
        }

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->addLine(new Line());

        $options = [
            'invoice' => $invoice,
            'form_options' => $client instanceof Client ? ['currency' => $client->getCurrency()] : [],
        ];

        return $this->handler->handle(InvoiceCreateHandler::class, $options);
    }
}
