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

namespace SolidInvoice\QuoteBundle\Action;

use Exception;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Handler\QuoteCreateHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;

final class Create
{
    public function __construct(
        private readonly ClientRepository $repository,
        private readonly FormHandler $handler
    ) {
    }

    /**
     * @return Template|FormRequest
     * @throws Exception
     */
    public function __invoke(Request $request, Client $client = null)
    {
        $totalClientsCount = $this->repository->getTotalClients();
        if (0 === $totalClientsCount) {
            return new Template('@SolidInvoiceQuote/Default/empty_clients.html.twig');
        }

        $quote = new Quote();
        $quote->setClient($client);
        $quote->addLine(new Line());

        if (1 === $totalClientsCount && ! $client instanceof Client) {
            $quote->setClient($this->repository->findOneBy([]));
        }

        $options = [
            'quote' => $quote,
            'form_options' => $client instanceof Client ? ['currency' => $client->getCurrency()] : [],
        ];

        return $this->handler->handle(QuoteCreateHandler::class, $options);
    }
}
