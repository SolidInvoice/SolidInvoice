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

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Security\EmailVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class VerifyEmail extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $id = $request->query->getString('id');

        try {
            $user = $this->userRepository->find(Ulid::fromString($id));
        } catch (\InvalidArgumentException) {
            return $this->invalid();
        }

        // Ensure the user exists in persistence
        if (! $user instanceof User) {
            return $this->invalid();
        }

        // validate the email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $this->translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('_login');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('_login');
    }

    private function invalid(): Response
    {
        $this->addFlash('error', 'The email verification link is invalid.');
        return $this->redirectToRoute('_login');
    }
}
