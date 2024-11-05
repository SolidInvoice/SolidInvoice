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

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Form\Handler\RegisterFormHandler;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

final class Register
{
    public function __construct(
        private readonly UserInvitationRepository $repository
    ) {
    }

    public function __invoke(Request $request, ToggleInterface $toggle, FormHandler $formHandler): FormRequest
    {
        $invitation = null;

        if ($request->query->has('invitation')) {
            $invitation = $this->repository->find(Ulid::fromString($request->query->get('invitation')));

            if (! $invitation instanceof UserInvitation) {
                throw new NotFoundHttpException('Invitation is not valid');
            }
        }

        if (! $request->query->has('invitation') && ! $toggle->isActive('allow_registration')) {
            throw new NotFoundHttpException('Registration is disabled');
        }

        return $formHandler->handle(RegisterFormHandler::class, ['invitation' => $invitation]);
    }
}
