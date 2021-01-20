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

namespace SolidInvoice\DataGridBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\SerializeTrait;
use SolidInvoice\DataGridBundle\Repository\GridRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Data implements AjaxResponse
{
    use SerializeTrait;

    /**
     * @var GridRepository
     */
    private $repository;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(GridRepository $repository, ManagerRegistry $registry)
    {
        $this->repository = $repository;
        $this->registry = $registry;
    }

    public function __invoke(Request $request, string $name): Response
    {
        $grid = $this->repository->find($name);

        $grid->setParameters($request->get('parameters', []));

        return $this->serialize($grid->fetchData($request, $this->registry->getManager()));
    }
}
