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

use FOS\UserBundle\Form\Factory\FactoryInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Manager\UserManager;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class UserEditFormHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FactoryInterface
     */
    private $factory;

    public function __construct(UserManager $userManager, FactoryInterface $factory, RouterInterface $router)
    {
        $this->userManager = $userManager;
        $this->router = $router;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, Options $options)
    {
        return $this->factory->createForm(['data' => $options->get('user')]);
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function onSuccess($user, FormRequest $form): ?Response
    {
        $this->userManager->updateUser($user);

        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse
        {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'users.create.success';
            }
        };
    }

    /**
     * Configure defined, required and default options.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
    }
}
