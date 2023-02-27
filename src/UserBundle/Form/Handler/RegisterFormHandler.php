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
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\RegisterType;
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

class RegisterFormHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    private UserPasswordHasherInterface $userPasswordHasher;

    private RouterInterface $router;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, RouterInterface $router)
    {
        $this->router = $router;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(RegisterType::class);
    }

    public function getResponse(FormRequest $formRequest): Template
    {
        return new Template(
            '@SolidInvoiceUser/Security/register.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    public function onSuccess(FormRequest $form, $data): ?Response
    {
        assert($data instanceof User);
        $data->setPassword($this->userPasswordHasher->hashPassword($data, $data->getPlainPassword()));
        $data->setUsername($data->getEmail());
        $data->setEnabled(true);
        $data->eraseCredentials();
        $this->save($data);

        $route = $this->router->generate('_dashboard');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'security.register.success';
            }
        };
    }
}
