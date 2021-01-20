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

namespace SolidInvoice\QuoteBundle\Action\Grid;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpFoundation\Request;

final class Delete implements AjaxResponse
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
