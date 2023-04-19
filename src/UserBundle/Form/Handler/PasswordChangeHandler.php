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

namespace SolidInvoice\UserBundle\Form\Handler;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\ChangePasswordType;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @see \SolidInvoice\UserBundle\Tests\Form\Handler\PasswordChangeHandlerTest
 */
class PasswordChangeHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface
{
    private UserRepositoryInterface $userRepository;

    private RouterInterface $router;

    private TokenStorageInterface $tokenStorage;

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserRepositoryInterface $userRepository, UserPasswordHasherInterface $userPasswordHasher, TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(ChangePasswordType::class, $options->get('user', $this->tokenStorage->getToken()->getUser()), ['confirm_password' => $options->get('confirm_password', true)]);
    }

    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceUser/ChangePassword/change_password.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * @param User $data
     */
    public function onSuccess(FormRequest $form, $data): ?Response
    {
        $route = $form->getOptions()->get('redirect_route', '_profile');

        $data->setPassword($this->userPasswordHasher->hashPassword($data, $data->getPlainPassword()));
        $data->eraseCredentials();

        $this->userRepository->save($data);

        $route = $this->router->generate($route);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'profile.password_change.success';
            }
        };
    }
}
