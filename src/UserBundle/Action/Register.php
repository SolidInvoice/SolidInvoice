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

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Form\Type\RegisterType;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;

final class Register extends AbstractController
{
    public function __construct(
        private readonly UserInvitationRepository $userInvitationRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly RouterInterface $router,
        private readonly ToggleInterface $toggle,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $invitation = null;

        if ($request->query->has('invitation')) {
            $invitation = $this->userInvitationRepository->find(Ulid::fromString($request->query->get('invitation')));

            if (! $invitation instanceof UserInvitation) {
                throw $this->createNotFoundException('Invitation is not valid');
            }
        }

        if (! $request->query->has('invitation') && ! $this->toggle->isActive('allow_registration')) {
            throw $this->createNotFoundException('Registration is disabled');
        }

        $form =
            $invitation instanceof UserInvitation ?
                $this->createForm(RegisterType::class, null, ['email' => $invitation->getEmail()]) :
                $this->createForm(RegisterType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            if ($invitation instanceof UserInvitation) {
                $user->setEmail($invitation->getEmail());
                $user->addCompany($invitation->getCompany());
            }

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPlainPassword()));
            $user->setEnabled(true);
            $user->eraseCredentials();
            $this->userRepository->save($user);

            if ($invitation instanceof UserInvitation) {
                $this->userInvitationRepository->delete($invitation);
            }

            $route = $this->router->generate('_login');

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield self::FLASH_SUCCESS => 'security.register.success';
                }
            };
        }

        return $this->render(
            '@SolidInvoiceUser/Security/register.html.twig',
            [
                'form' => $form,
            ]
        );
    }
}
