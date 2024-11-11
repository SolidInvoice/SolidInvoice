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

namespace SolidInvoice\UserBundle\Action\Security;

use SolidInvoice\CoreBundle\Templating\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class Login
{
    public function __invoke(Request $request, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $session = $request->getSession();

        $authErrorKey = SecurityRequestAttributes::AUTHENTICATION_ERROR;
        $lastUsernameKey = SecurityRequestAttributes::LAST_USERNAME;
        $error = null;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif ($session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        }

        if (! $error instanceof AuthenticationException) {
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
