<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Action\ForgotPassword;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\MailerBundle\MailerInterface;
use SolidInvoice\UserBundle\Email\ResetPasswordEmail;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

final class Send
{
    public function __invoke(Request $request, UserRepository $userRepository, RouterInterface $router, MailerInterface $mailer)
    {
        $username = $request->request->get('username');

        try {
            $user = $userRepository->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            $route = $router->generate('_user_forgot_password');

            return new class($route, $e->getMessage()) extends RedirectResponse implements FlashResponse {
                private $message;

                public function __construct($route, $message)
                {
                    parent::__construct($route);
                    $this->message = $message;
                }

                public function getFlash(): iterable
                {
                    yield self::FLASH_DANGER => $this->message;
                }
            };
        }

        if (!$user->isPasswordRequestNonExpired(60 * 60 * 3)) {
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken(rtrim(base64_encode(bin2hex(random_bytes(32))), '='));
            }

            $mailer->send(new ResetPasswordEmail($user));
            $user->setPasswordRequestedAt(new \DateTime());
            $userRepository->save($user);
        }

        return new RedirectResponse($router->generate('_user_forgot_password_check_email', ['username' => $username]));
    }
}
