<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action\Grid;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\HttpFoundation\Request;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var InvoiceRepository
     */
    private $repository;

    public function __construct(InvoiceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request)
    {
        $this->repository->deleteInvoices($request->request->get('data'));

        return $this->json([]);
    }
}
