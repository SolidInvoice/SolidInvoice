<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\UserBundle\Action\ForgotPassword;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\UserBundle\Form\Handler\PasswordChangeHandler;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Reset
{
    public function __invoke(Request $request, UserRepository $userRepository, FormHandler $formHandler, string $token)
    {
        $user = $userRepository->findOneBy(['confirmationToken' => $token]);

        if (null === $user) {
            throw new NotFoundHttpException();
        }

        try {
            $response = $formHandler->handle(PasswordChangeHandler::class, ['confirm_password' => false, 'user' => $user, 'redirect_route' => '_login']);

            return $response;
        } finally {

            if ($request->isMethod(Request::METHOD_POST) && $response->getResponse() instanceof FlashResponse) {
                $userRepository->clearUserConfirmationToken($user);
            }
        }
    }
}
