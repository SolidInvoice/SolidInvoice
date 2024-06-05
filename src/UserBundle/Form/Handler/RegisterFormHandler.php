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
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Form\Type\RegisterType;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use function assert;

class RegisterFormHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerOptionsResolver
{
    use SaveableTrait;

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly RouterInterface $router,
        private readonly UserInvitationRepository $invitationRepository
    ) {
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        $invitation = $options->get('invitation');

        if ($invitation instanceof UserInvitation) {
            return $factory->create(RegisterType::class, null, ['email' => $invitation->getEmail()]);
        }

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

        $invitation = $form->getOptions()->get('invitation');

        if ($invitation instanceof UserInvitation) {
            $data->setEmail($invitation->getEmail());
            $data->addCompany($invitation->getCompany());
        }

        $data->setPassword($this->userPasswordHasher->hashPassword($data, $data->getPlainPassword()));
        $data->setEnabled(true);
        $data->eraseCredentials();
        $this->save($data);

        if ($invitation instanceof UserInvitation) {
            $this->invitationRepository->delete($invitation);
        }

        $route = $this->router->generate('_dashboard');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'security.register.success';
            }
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('invitation');
        $resolver->setAllowedTypes('invitation', ['null', UserInvitation::class]);
    }
}
