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

use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(name: 'NotificationIntegrations')]
final class NotificationIntegrations extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private readonly TransportSettingRepository $repository
    ) {
    }

    #[LiveProp(writable: true, url: true)]
    public ?string $setting = null;

    #[LiveProp(writable: true, url: true)]
    public ?string $action = null;

    /**
     * @return list<TransportSetting>
     */
    #[ExposeInTemplate]
    public function enabledIntegrations(): array
    {
        return $this->repository->findBy(['user' => $this->getUser()], ['name' => 'ASC']);
    }

    #[ExposeInTemplate]
    public function integration(): ?TransportSetting
    {
        if ($this->setting === '' || null === $this->setting) {
            return null;
        }

        return $this->repository->find($this->setting);
    }
}
