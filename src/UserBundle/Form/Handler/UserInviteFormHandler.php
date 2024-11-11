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
use Generator;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Form\Type\UserInviteType;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\UserInvitation\UserInvitation as SendUserInvitation;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function count;

/**
 * @see \SolidInvoice\UserBundle\Tests\Form\Handler\UserInviteFormHandlerTest
 */
class UserInviteFormHandler implements FormHandlerResponseInterface, FormHandlerInterface, FormHandlerSuccessInterface
{
    use SaveableTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly SendUserInvitation $userInvitation
    ) {
    }

    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        return $factory->create(UserInviteType::class);
    }

    public function getResponse(FormRequest $formRequest): Template
    {
        return new Template(
            '@SolidInvoiceUser/Users/invite.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
            ]
        );
    }

    /**
     * @param UserInvitation $data
     *
     * @throws Exception
     */
    public function onSuccess(FormRequest $form, $data): ?Response
    {
        assert($data instanceof UserInvitation);

        $existingUser = $this->userRepository->findOneBy(['email' => $data->getEmail()]);

        if ($existingUser instanceof User) {
            return $this->userAlreadyExistsResponse();
        }

        $invitedBy = $this->security->getUser();
        assert($invitedBy instanceof User);

        $data->setCompany($this->companyRepository->find($this->companySelector->getCompany()));
        $data->setInvitedBy($invitedBy);
        $data->setStatus(UserInvitation::STATUS_PENDING);

        $validation = $this->validator->validate($data);

        if (count($validation) > 0) {
            return $this->userAlreadyInvitedResponse($validation);
        }

        $this->save($data);

        $this->userInvitation->sendUserInvitation($data);

        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'users.invitation.success';
            }
        };
    }

    private function userAlreadyInvitedResponse(ConstraintViolationListInterface $validation): RedirectResponse
    {
        $route = $this->router->generate('_users_list');

        return new class($validation, $route) extends RedirectResponse implements FlashResponse {
            public function __construct(
                private readonly ConstraintViolationListInterface $validation,
                string $route
            ) {
                parent::__construct($route);
            }

            public function getFlash(): Generator
            {
                foreach ($this->validation as $violation) {
                    yield self::FLASH_ERROR => $violation->getMessage();
                }
            }
        };
    }

    private function userAlreadyExistsResponse(): RedirectResponse
    {
        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_ERROR => 'User already has access to the company.';
            }
        };
    }
}
