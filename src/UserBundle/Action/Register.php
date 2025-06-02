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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Ulid;
use function assert;

final class Register extends AbstractController
{
    public function __construct(
        private readonly UserInvitationRepository $repository,
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
            $invitation = $this->repository->find(Ulid::fromString($request->query->get('invitation')));

            if (! $invitation instanceof UserInvitation) {
                throw new NotFoundHttpException('Invitation is not valid');
            }
        }

        if (! $request->query->has('invitation') && ! $toggle->isActive('allow_registration')) {
            throw new NotFoundHttpException('Registration is disabled');
        }

        $form = $this->getForm($invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            assert($data instanceof Registration);

            $user = new User();

            if ($invitation instanceof UserInvitation) {
                $user->setEmail($invitation->getEmail());
                $user->addCompany($invitation->getCompany());
            } else {
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
            }

            return $this->security->login($user, 'security.authenticator.form_login.main', 'main');
        }

        return $this->render('@SolidInvoiceUser/Security/register.html.twig', ['form' => $form]);
    }

    public function getForm(?UserInvitation $invitation = null): FormInterface
    {
        $options = [];

        if ($invitation instanceof UserInvitation) {
            $options['email'] = $invitation->getEmail();
        }

        return $this->createForm(RegisterType::class, null, $options);
    }
}
