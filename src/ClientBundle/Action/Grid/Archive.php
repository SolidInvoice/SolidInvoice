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

namespace SolidInvoice\ClientBundle\Action\Grid;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Archive implements AjaxResponse
{
    use JsonTrait;

    public function __construct(private readonly ClientRepository $repository)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->repository->archiveClients((array) $request->request->get('data'));

        return $this->json([]);
    }
}
