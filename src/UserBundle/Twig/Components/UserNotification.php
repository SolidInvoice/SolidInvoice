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
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Entity\UserNotification as UserNotificationEntity;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use SolidInvoice\NotificationBundle\Repository\UserNotificationRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\NotificationSettingType;
use SolidInvoice\UserBundle\Form\Type\NotificationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class UserNotification extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true, url: true)]
    public string $action = '';

    /**
     * @var list<string>
     */
    public array $notificationList = [];

    public function __construct(
        private readonly UserNotificationRepository $userNotificationRepository,
        private readonly TransportSettingRepository $transportSettingRepository,
        #[TaggedLocator('solid_invoice_notification.notification', 'name')]
        ServiceLocator $notificationList,
    ) {
        $this->notificationList = array_keys($notificationList->getProvidedServices());
    }

    protected function instantiateForm(): FormInterface
    {
        $formData = [];

        foreach ($this->userNotificationRepository->findBy(['user' => $this->getUser()]) as $userNotification) {
            $event = $userNotification->getEvent();

            $formData[$event] = [
                'event' => $event,
                'transports' => [],
            ];

            foreach ($userNotification->getTransports() as $transport) {
                $formData[$event]['transports'][] = $transport->getId()->toString();
            }

            if ($userNotification->isEmail()) {
                $formData[$event]['transports'][] = NotificationSettingType::EMAIL_NOTIFICATION;
            }
        }

        return $this->createForm(NotificationType::class, $formData);
    }

    /**
     * @return list<UserNotificationEntity>
     */
    #[ExposeInTemplate]
    public function allNotifications(): array
    {
        return $this->userNotificationRepository->findAll();
    }

    /**
     * @return list<TransportSetting>
     */
    #[ExposeInTemplate]
    public function allTransports(): array
    {
        return $this->transportSettingRepository->findAll();
    }

    #[LiveAction()]
    public function save(ManagerRegistry $registry): Response
    {
        $this->submitForm();

        $submittedData = $this->form?->getData();

        $user = $this->getUser();
        assert($user instanceof User);

        $em = $registry->getManagerForClass(UserNotificationEntity::class);
        assert($em instanceof EntityManagerInterface);

        foreach ($submittedData as $notification) {
            $userNotification = $this->userNotificationRepository->findOneBy(['event' => $notification['event'], 'user' => $user]);

            if (! $userNotification instanceof UserNotificationEntity) {
                $userNotification = new UserNotificationEntity();
                $userNotification->setEvent($notification['event']);
                $userNotification->setUser($user);
            }

            $userNotification->setEmail(false);

            $existingTransports = $userNotification->getTransports();

            foreach ($existingTransports as $existingTransport) {
                $userNotification->removeTransport($existingTransport);
            }

            foreach ($notification['transports'] as $transportId) {
                if ($transportId === NotificationSettingType::EMAIL_NOTIFICATION) {
                    $userNotification->setEmail(true);
                    continue;
                }

                $transport = $this->transportSettingRepository->find($transportId);

                if (! $transport instanceof TransportSetting) {
                    continue;
                }

                $userNotification->addTransport($transport);
            }

            $em->persist($userNotification);
        }

        $em->flush();

        $this->addFlash('success', 'Notification saved!');

        return $this->redirectToRoute('_profile_notifications');
    }
}
