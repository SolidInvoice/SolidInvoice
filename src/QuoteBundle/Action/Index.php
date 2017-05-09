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

use CSBill\CoreBundle\Templating\Template;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
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
            '@CSBillQuote/Default/index.html.twig',
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
