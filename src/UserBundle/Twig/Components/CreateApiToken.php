<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Form\Type\ApiTokenType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CreateApiToken extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use ComponentWithFormTrait;

    public const API_TOKEN_CREATED_EVENT = 'api.token.created';

    public function __construct(
        private readonly Security $security,
        private readonly ApiTokenManager $apiTokenManager,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ApiTokenType::class);
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();

        /** @var ApiToken $token */
        $token = $this->getForm()->getData();
        $token->setUser($this->security->getUser());
        $token->setToken($this->apiTokenManager->generateToken());

        $entityManager->persist($token);
        $entityManager->flush();

        $this->addFlash('success', 'Api Token created');

        $this->emit(self::API_TOKEN_CREATED_EVENT);
        $this->dispatchBrowserEvent('modal:close');

        $this->resetForm();
    }
}
