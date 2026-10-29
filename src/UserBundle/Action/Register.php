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

use SolidInvoice\UserBundle\DTO\Registration;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\SignupMethod;
use SolidInvoice\UserBundle\Form\Type\RegisterType;
use SolidInvoice\UserBundle\Registration\UserRegistrar;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use function assert;

final class Register extends AbstractController
{
    public function __construct(
        private readonly UserRegistrar $userRegistrar,
        private readonly UserInvitationRepository $invitationRepository,
        private readonly Security $security,
        private readonly ToggleInterface $toggle,
        #[Autowire(env: 'SOLIDINVOICE_TURNSTILE_SITE_KEY')]
        private readonly ?string $turnstileSiteKey = null,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $invitation = null;

        if ($request->query->has('invitation')) {
            $invitationId = $request->query->getString('invitation');

            if (! Ulid::isValid($invitationId, Ulid::FORMAT_BASE_32)) {
                throw $this->createNotFoundException('Invitation is not valid');
            }

            $invitation = $this->invitationRepository->find(Ulid::fromString($invitationId));

            if (! $invitation instanceof UserInvitation) {
                throw $this->createNotFoundException('Invitation is not valid');
            }

            if ($invitation->isExpired()) {
                $this->invitationRepository->save($invitation->markExpired());

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
            $data = $form->getData();
            assert($data instanceof Registration);

            $user = $this->userRegistrar->register(
                (string) ($invitation instanceof UserInvitation ? $invitation->getEmail() : $data->email),
                (string) $data->plainPassword,
                $invitation instanceof UserInvitation ? SignupMethod::Invitation : SignupMethod::Registration,
                // Invited users join the inviting company; everyone else creates one during onboarding.
                $invitation?->getCompany(),
            );

            if ($invitation instanceof UserInvitation) {
                $this->invitationRepository->delete($invitation);
            }

            // Auto-login and redirect
            // OnboardingLoginListener will handle post-login redirect:
            // - Invited users: Skip onboarding (they have a company)
            // - Regular users: Redirect to onboarding
            return $this->security->login($user, 'security.authenticator.form_login.main', 'main');
        }

        return $this->render('@SolidInvoiceUser/Security/register.html.twig', ['form' => $form, 'turnstile_site_key' => $this->turnstileSiteKey]);
    }
}
