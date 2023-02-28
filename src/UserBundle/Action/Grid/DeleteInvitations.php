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

namespace SolidInvoice\UserBundle\Action\Grid;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\ConversionException;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class DeleteInvitations implements AjaxResponse
{
    use JsonTrait;

    private UserInvitationRepository $repository;

    public function __construct(UserInvitationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @throws ConversionException|Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->repository->deleteInvitations($request->request->all('data'));

        return $this->json([]);
    }
}
