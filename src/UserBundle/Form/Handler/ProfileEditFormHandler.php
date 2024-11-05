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
use SolidInvoice\UserBundle\Form\Type\ProfileType;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
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

class ProfileEditFormHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RouterInterface $router
    ) {
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(ProfileType::class, $this->tokenStorage->getToken()?->getUser());
    }

    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceUser/Profile/edit.html.twig',
            [
                'form' => $formRequest->getForm()?->createView(),
            ]
        );
    }

    public function onSuccess(FormRequest $form, $data): ?Response
    {
        $this->userRepository->save($data);

        $route = $this->router->generate('_profile');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'profile.edit.success';
            }
        };
    }
}
