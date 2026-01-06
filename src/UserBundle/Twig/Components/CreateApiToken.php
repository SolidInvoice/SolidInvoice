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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
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

    #[LiveProp]
    public ?string $createdToken = null;

    #[LiveProp]
    public ?string $createdTokenName = null;

    public function __construct(
        private readonly Security $security,
        private readonly ApiTokenManager $apiTokenManager,
    ) {
    }

    public function getModalTitle(): string
    {
        return $this->createdToken ? 'API Token Created Successfully' : 'Create New API Token';
    }

    public function getModalStatus(): string
    {
        return $this->createdToken ? 'success' : '';
    }

    public function shouldShowModal(): bool
    {
        return (bool) $this->createdToken;
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

        // Store token for one-time display
        $this->createdToken = $token->getToken();
        $this->createdTokenName = $token->getName();

        $this->addFlash('success', 'API Token created successfully');

        $this->emit(self::API_TOKEN_CREATED_EVENT);

        $this->resetForm();
    }

    #[LiveAction]
    public function clearToken(): void
    {
        $this->createdToken = null;
        $this->createdTokenName = null;
    }
}
