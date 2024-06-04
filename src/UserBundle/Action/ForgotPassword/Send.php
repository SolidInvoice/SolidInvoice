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

namespace SolidInvoice\UserBundle\Action\ForgotPassword;

use DateTime;
use Exception;
use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\UserBundle\Email\ResetPasswordEmail;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use function assert;

final class Send
{
    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function __invoke(Request $request, UserRepository $userRepository, RouterInterface $router, MailerInterface $mailer): RedirectResponse
    {
        $username = $request->request->get('username');

        try {
            $user = $userRepository->loadUserByIdentifier($username);
            assert($user instanceof User);
        } catch (UserNotFoundException $e) {
            $route = $router->generate('_user_forgot_password');

            return new class($route, $e->getMessage()) extends RedirectResponse implements FlashResponse {
                public function __construct(
                    string $route,
                    private readonly string $message
                ) {
                    parent::__construct($route);
                }

                public function getFlash(): Generator
                {
                    yield self::FLASH_DANGER => $this->message;
                }
            };
        }

        if (! $user->isPasswordRequestNonExpired(60 * 60 * 3)) {
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken(rtrim(base64_encode(bin2hex(random_bytes(32))), '='));
            }

            $mailer->send(new ResetPasswordEmail($user));
            $user->setPasswordRequestedAt(new DateTime());
            $userRepository->save($user);
        }

        return new RedirectResponse($router->generate('_user_forgot_password_check_email', ['username' => $username]));
    }
}
