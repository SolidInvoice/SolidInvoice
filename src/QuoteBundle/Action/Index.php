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

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpFoundation\Request;

final class Index
{
    /**
     * @var QuoteRepository
     */
    private $repository;

    public function __construct(QuoteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request)
    {
        return new Template(
            '@SolidInvoiceQuote/Default/index.html.twig',
            [
                'status_list_count' => [
                    Graph::STATUS_PENDING => $this->repository->getTotalQuotes(Graph::STATUS_PENDING),
                    Graph::STATUS_ACCEPTED => $this->repository->getTotalQuotes(Graph::STATUS_ACCEPTED),
                    Graph::STATUS_CANCELLED => $this->repository->getTotalQuotes(Graph::STATUS_CANCELLED),
                    Graph::STATUS_DRAFT => $this->repository->getTotalQuotes(Graph::STATUS_DRAFT),
                    Graph::STATUS_DECLINED => $this->repository->getTotalQuotes(Graph::STATUS_DECLINED),
                ],
            ]
        );
    }
}
