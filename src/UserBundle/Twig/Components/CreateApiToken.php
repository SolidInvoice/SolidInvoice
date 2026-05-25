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

use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\ApiTokenType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    public const string API_TOKEN_CREATED_EVENT = 'api.token.created';

    /**
     * Plaintext of the most recently created token, exposed to the template
     * exactly once after a successful create. Cleared by clearToken() so it
     * does not persist across re-renders.
     */
    #[LiveProp]
    public ?string $createdToken = null;

    #[LiveProp]
    public ?string $createdTokenName = null;

    public function __construct(
        private readonly Security $security,
        private readonly ApiTokenManager $apiTokenManager,
        private readonly FeatureGate $featureGate,
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

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ApiTokenType::class);
    }

    #[LiveAction]
    public function save(): void
    {
        if (! $this->featureGate->isEnabled('rest_api_access')) {
            throw new AccessDeniedException('REST API access is not available on the current plan.');
        }

        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();

        /** @var ApiToken $formData */
        $formData = $this->getForm()->getData();

        /** @var User $user */
        $user = $this->security->getUser();

        $generated = $this->apiTokenManager->create(
            $user,
            (string) $formData->getName(),
            $formData->getDescription(),
        );

        // Plaintext is shown exactly once; the DB only stores the hash.
        $this->createdToken = $generated->plaintext;
        $this->createdTokenName = $generated->token->getName();

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
