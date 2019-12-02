<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\UserBundle\Action\Security;

use SolidInvoice\CoreBundle\Templating\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class Login
{
    public function __invoke(Request $request, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;
        $error = null;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } else if ($session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        return new Template(
            '@SolidInvoiceUser/Security/login.html.twig',
            [
                'last_username' => $session->get($lastUsernameKey),
                'error' => $error,
                'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
            ]
        );
    }
}
