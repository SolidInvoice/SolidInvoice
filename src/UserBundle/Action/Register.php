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

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\DTO\Registration;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Form\Type\RegisterType;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Ulid;
use function assert;

final class Register extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly UserInvitationRepository $invitationRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(Request $request, ToggleInterface $toggle): Response
    {
        $invitation = null;

        if ($request->query->has('invitation')) {
            $invitationId = $request->query->getString('invitation');

            if (! Ulid::isValid($invitationId)) {
                throw $this->createNotFoundException('Invitation is not valid');
            }

            $invitation = $this->invitationRepository->find(Ulid::fromString($invitationId));

            if (! $invitation instanceof UserInvitation) {
                throw $this->createNotFoundException('Invitation is not valid');
            }
        }

        if (! $request->query->has('invitation') && ! $toggle->isActive('allow_registration')) {
            throw $this->createNotFoundException('Registration is disabled');
        }

        $form =
            $invitation instanceof UserInvitation ?
                $this->createForm(RegisterType::class, null, ['email' => $invitation->getEmail()]) :
                $this->createForm(RegisterType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            assert($data instanceof Registration);

            $user = new User();
            $user->setEmail($invitation instanceof UserInvitation ? $invitation->getEmail() : $data->email);
            $user->setPassword($data->plainPassword);

            // If invited, add to existing company
            if ($invitation instanceof UserInvitation) {
                $user->addCompany($invitation->getCompany());
                $this->invitationRepository->delete($invitation);
            }
            // For regular users, company will be created during onboarding

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPassword()));
            $user->setEnabled(true);
            $user->eraseCredentials();
            $this->userRepository->save($user);

            // Auto-login and redirect
            // OnboardingLoginListener will handle post-login redirect:
            // - Invited users: Skip onboarding (they have a company)
            // - Regular users: Redirect to onboarding
            return $this->security->login($user, 'security.authenticator.form_login.main', 'main');
        }

        return $this->render('@SolidInvoiceUser/Security/register.html.twig', ['form' => $form]);
    }
}
