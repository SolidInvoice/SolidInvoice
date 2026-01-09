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

use SolidInvoice\UserBundle\DTO\ChangePassword as ChangePasswordDTO;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\ChangePasswordType;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use function assert;

final class ChangePassword
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @return array{form: FormView}|Response
     */
    #[Template('@SolidInvoiceUser/ChangePassword/change_password.html.twig')]
    public function __invoke(Request $request): array | Response
    {
        $changePasswordDTO = new ChangePasswordDTO();
        $form = $this->formFactory->create(ChangePasswordType::class, $changePasswordDTO, ['confirm_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $this->tokenStorage->getToken();
            if (null === $token) {
                throw new AccessDeniedException('User must be authenticated to change password.');
            }

            $user = $token->getUser();
            if (! $user instanceof User) {
                throw new AccessDeniedException('User must be authenticated to change password.');
            }

            // Hash the NEW plain password from the DTO and set it on the User entity
            if (null !== $changePasswordDTO->plainPassword) {
                $hashedPassword = $this->userPasswordHasher->hashPassword($user, $changePasswordDTO->plainPassword);
                $user->setPassword($hashedPassword);
            }

            $user->eraseCredentials();

            $this->userRepository->save($user);

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'profile.password_change.success');

            return new RedirectResponse($this->router->generate('_profile'));
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
