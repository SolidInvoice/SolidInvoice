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

use Exception;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\UserType;
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

/**
 * @see \SolidInvoice\UserBundle\Tests\Form\Handler\UserAddFormHandlerTest
 */
class UserAddFormHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    private RouterInterface $router;

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher, RouterInterface $router)
    {
        $this->router = $router;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(UserType::class);
    }

    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoiceUser/Users/form.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * @param User $data
     *
     * @throws Exception
     */
    public function onSuccess(FormRequest $form, $data): ?Response
    {
        $data->setPassword($this->userPasswordHasher->hashPassword($data, $data->getPlainPassword()));
        $data->eraseCredentials();
        $this->save($data);

        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): \Generator
            {
                yield self::FLASH_SUCCESS => 'users.create.success';
            }
        };
    }
}
