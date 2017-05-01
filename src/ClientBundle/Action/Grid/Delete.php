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

namespace CSBill\ClientBundle\Action\Grid;

use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Traits\JsonTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Delete implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var ClientRepository
     */
    private $repository;

    /**
     * @param ClientRepository $repository
     */
    public function __construct(ClientRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->repository->deleteClients($request->request->get('data'));

        return $this->json([]);
    }
}
