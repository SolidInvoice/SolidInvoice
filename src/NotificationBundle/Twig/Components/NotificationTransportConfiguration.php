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

namespace SolidInvoice\NotificationBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Form\Type\TransportSettingType;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function assert;

#[AsLiveComponent]
final class NotificationTransportConfiguration extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * @var TransportSetting|string|null
     */
    #[LiveProp(writable: true, fieldName: 'formData', updateFromParent: true)]
    public $setting = null;

    #[LiveProp(writable: true, updateFromParent: true, url: true)]
    public ?string $type = null;

    #[LiveProp(writable: true, updateFromParent: true, url: true)]
    public ?string $transport = null;

    #[LiveProp(writable: true)]
    public ?bool $showDeleteConfirmation = false;

    /**
     * @param ServiceLocator<ConfiguratorInterface> $transportConfigurations
     */
    public function __construct(
        #[TaggedLocator(tag: ConfiguratorInterface::DI_TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $transportConfigurations,
        private readonly TransportSettingRepository $repository
    ) {
    }

    #[ExposeInTemplate]
    public function transportSetting(): TransportSetting
    {
        // If setting is a string (ID from URL), load it from the repository
        if (is_string($this->setting) && $this->setting !== '') {
            $loadedSetting = $this->repository->find(Ulid::fromString($this->setting));

            if (! $loadedSetting instanceof TransportSetting) {
                throw $this->createNotFoundException(sprintf('Integration with ID "%s" not found', $this->setting));
            }

            // Verify ownership
            if ($loadedSetting->getUser() !== $this->getUser()) {
                throw $this->createAccessDeniedException('You do not have permission to access this integration');
            }

            $this->setting = $loadedSetting;
        }

        // If setting is already a TransportSetting object, use it
        if ($this->setting instanceof TransportSetting) {
            return $this->setting;
        }

        // Create new TransportSetting for adding new integration
        $this->setting = new TransportSetting();

        // Pre-populate transport if specified in URL (e.g., when clicking "Configure" on a specific integration)
        if ($this->transport !== null && $this->transport !== '') {
            $this->setting->setTransport($this->transport);
        }

        return $this->setting;
    }

    #[ExposeInTemplate]
    public function notificationType(): string
    {
        return match ($this->type) {
            'texter' => 'SMS',
            'chatter' => 'Chat',
            default => 'Unknown',
        };
    }

    #[ExposeInTemplate]
    public function isNewSetting(): bool
    {
        return $this->transportSetting()->getId() === null;
    }

    /**
     * @throws LogicException
     */
    protected function instantiateForm(): FormInterface
    {
        $setting = $this->transportSetting();

        // For existing integrations (with an ID), get the type from the transport if not provided
        if (($this->type === null || $this->type === '') && ! $this->isNewSetting()) {
            $this->type = $this->transportConfigurations->get($setting->getTransport())->getType();
        }

        // Ensure type is set for new integrations
        if ($this->type === null || $this->type === '') {
            throw new LogicException('Transport type must be specified for new integrations');
        }

        return $this->createForm(
            TransportSettingType::class,
            $setting,
            [
                'type' => $this->type,
            ]
        );
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager, RequestStack $requestStack): Response
    {
        if ($this->setting !== null && $this->setting->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->submitForm();

        $form = $this->getForm();

        // If form is not valid, redirect back to show validation errors
        if (! $form->isValid()) {
            $session = $requestStack->getSession();
            assert($session instanceof Session);

            $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'Please correct the validation errors');

            return $this->redirectToRoute('_notification_integration');
        }

        /** @var TransportSetting $setting */
        $setting = $form->getData();

        // Check if it's a new integration before persisting (after persist, it will have an ID)
        $isNew = $this->isNewSetting();

        $user = $this->getUser();
        assert($user instanceof User);
        $setting->setUser($user);

        $entityManager->persist($setting);
        $entityManager->flush();

        $session = $requestStack->getSession();
        assert($session instanceof Session);

        $session->getFlashBag()->add(
            FlashResponse::FLASH_SUCCESS,
            $isNew ? 'Integration added' : 'Integration updated'
        );

        return $this->redirectToRoute('_notification_integration');
    }

    #[LiveAction]
    public function showDeleteConfirmation(): void
    {
        $this->showDeleteConfirmation = true;
    }

    #[LiveAction]
    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
    }

    #[LiveAction]
    public function confirmDelete(EntityManagerInterface $entityManager, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        assert($session instanceof Session);

        $setting = $this->transportSetting();

        // Check if the integration exists in the database (new entities don't have an ID)
        if ($this->isNewSetting()) {
            $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'Integration does not exist');

            return $this->redirectToRoute('_notification_integration');
        }

        // Verify ownership before deleting
        if ($setting->getUser() !== $this->getUser()) {
            $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'You do not have permission to delete this integration');

            return $this->redirectToRoute('_notification_integration');
        }

        $entityManager->remove($setting);
        $entityManager->flush();

        $session->getFlashBag()->add(FlashResponse::FLASH_INFO, 'Integration deleted');

        return $this->redirectToRoute('_notification_integration');
    }
}
