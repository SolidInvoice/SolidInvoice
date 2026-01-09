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

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\ProfileType;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use function assert;

final class EditProfile
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @return array{form: FormView}|Response
     */
    #[Template('@SolidInvoiceUser/Profile/edit.html.twig')]
    public function __invoke(Request $request): array | Response
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (! $user instanceof User) {
            throw new AccessDeniedException('User must be authenticated to edit profile.');
        }

        $form = $this->formFactory->create(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->save($user);

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'profile.edit.success');

            return new RedirectResponse($this->router->generate('_profile'));
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
