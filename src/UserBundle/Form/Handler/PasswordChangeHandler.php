<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Form\Handler;

use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\UserBundle\Form\Type\ChangePasswordType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PasswordChangeHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    public function __construct(UserRepositoryInterface $userRepository, UserPasswordEncoderInterface $userPasswordEncoder, TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(ChangePasswordType::class, $options->get('user', $this->tokenStorage->getToken()->getUser()), ['confirm_password' => $options->get('confirm_password', true)]);
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     *
     * @var User
     */
    public function onSuccess(FormRequest $form, $user): ?Response
    {
        $route = $form->getOptions()->get('redirect_route', '_profile');

        $user->setPassword($this->userPasswordEncoder->encodePassword($user, $user->getPlainPassword()));
        $user->eraseCredentials();

        $this->userRepository->save($user);

        $route = $this->router->generate($route);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'profile.password_change.success';
            }
        };
    }
}
