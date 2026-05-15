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

use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
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

    /**
     * Plaintext of the most recently created token, exposed to the template
     * exactly once after a successful create. Cleared as soon as the modal
     * is dismissed so it does not persist across re-renders.
     */
    #[LiveProp(writable: true)]
    public ?string $newPlaintextToken = null;

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
    public function save(): void
    {
        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();

        /** @var ApiToken $token */
        $token = $this->getForm()->getData();

        /** @var User $user */
        $user = $this->security->getUser();

        $generated = $this->apiTokenManager->create($user, (string) $token->getName());

        $this->newPlaintextToken = $generated->plaintext;

        $this->emit(self::API_TOKEN_CREATED_EVENT);

        $this->resetForm();
    }

    #[LiveAction]
    public function dismissNewToken(): void
    {
        $this->newPlaintextToken = null;
        $this->dispatchBrowserEvent('modal:close');
    }
}
