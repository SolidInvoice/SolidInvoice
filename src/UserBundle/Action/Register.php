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
use SolidInvoice\UserBundle\DTO\InvitedRegistration;
use SolidInvoice\UserBundle\DTO\Registration;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Form\Type\InvitedRegisterType;
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
            try {
                $invitation = $this->invitationRepository->find(Ulid::fromString($request->query->get('invitation')));
            } catch (\InvalidArgumentException) {
                throw $this->createNotFoundException('Invitation is not valid');
            }

            if (! $invitation instanceof UserInvitation) {
                throw $this->createNotFoundException('Invitation is not valid');
            }
        }

        if (! $request->query->has('invitation') && ! $toggle->isActive('allow_registration')) {
            throw $this->createNotFoundException('Registration is disabled');
        }

        $form =
            $invitation instanceof UserInvitation ?
                $this->createForm(InvitedRegisterType::class, null, ['email' => $invitation->getEmail()]) :
                $this->createForm(RegisterType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();

            if ($invitation instanceof UserInvitation) {
                $data = $form->getData();
                assert($data instanceof InvitedRegistration);

                $user->setEmail($invitation->getEmail());
                $user->setFirstName($data->firstName);
                $user->setLastName($data->lastName);
                $user->addCompany($invitation->getCompany());
            } else {
                $data = $form->getData();
                assert($data instanceof Registration);

                $user->setEmail($data->email);
                $company = (new Company())->setName($data->company);
                $company->currency = 'USD'; // @TODO: Make this configurable, or get the currency from registration
                $user->addCompany($company);
            }

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $data->plainPassword));
            $user->setEnabled(true);
            $user->eraseCredentials();
            $this->userRepository->save($user);

            if ($invitation instanceof UserInvitation) {
                $this->invitationRepository->delete($invitation);
                $this->addFlash('success', 'security.register.invited.success');
            }

            return $this->security->login($user, 'security.authenticator.form_login.main', 'main');
        }

        $template = $invitation instanceof UserInvitation
            ? '@SolidInvoiceUser/Security/register-invited.html.twig'
            : '@SolidInvoiceUser/Security/register.html.twig';

        return $this->render($template, [
            'form' => $form,
            'invitation' => $invitation,
        ]);
    }
}
