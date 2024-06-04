<?php

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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Form\Type\TransportSettingType;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent()]
final class NotificationTransportConfiguration extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true, fieldName: 'formData', updateFromParent: true)]
    public ?TransportSetting $setting = null;

    #[LiveProp(writable: true, updateFromParent: true, url: true)]
    public ?string $type = null;

    /**
     * @param ServiceLocator<ConfiguratorInterface> $transportConfigurations
     */
    public function __construct(
        #[TaggedLocator(tag: ConfiguratorInterface::DI_TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $transportConfigurations
    ) {
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function instantiateForm(): FormInterface
    {
        if ($this->setting instanceof TransportSetting && $this->type === null) {
            $this->type = $this->transportConfigurations->get($this->setting->getTransport())->getType();
        }

        return $this->createForm(
            TransportSettingType::class,
            $this->setting,
            [
                'type' => $this->type
            ]
        );
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->submitForm();

        /** @var TransportSetting $setting */
        $setting = $this->getForm()->getData();

        $user = $this->getUser();
        assert($user instanceof User);
        $setting->setUser($user);

        $entityManager->persist($setting);
        $entityManager->flush();

        $this->addFlash('success', 'Integration added');

        return $this->redirectToRoute('_notification_integration');
    }
}
