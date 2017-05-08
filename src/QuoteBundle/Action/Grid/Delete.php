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

namespace CSBill\QuoteBundle\Action\Grid;

use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpFoundation\Request;

class Delete
{
    use JsonTrait;

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
        $this->repository->deleteQuotes($request->request->get('data'));

        return $this->json([]);
    }
}
