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

namespace SolidInvoice\UserBundle\Action\Ajax;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Form\Handler\ApiFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ApiCreate implements AjaxResponse
{
    public function __construct(
        private readonly FormHandler $handler,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public function __invoke(Request $request): FormRequest
    {
        $apiToken = new ApiToken();
        $apiToken->setUser($this->tokenStorage->getToken()->getUser());

        return $this->handler->handle(ApiFormHandler::class, ['api_token' => $apiToken]);
    }
}
