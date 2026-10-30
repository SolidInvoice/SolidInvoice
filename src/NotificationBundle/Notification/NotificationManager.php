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

namespace SolidInvoice\NotificationBundle\Notification;

use Psr\Log\LoggerInterface;
use ReflectionObject;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Traits\FlashErrorTrait;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Exception\InvalidNotificationMessageException;
use SolidInvoice\NotificationBundle\Repository\UserNotificationRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationManager
{
    use FlashErrorTrait;

    /**
     * @param ServiceLocator<ConfiguratorInterface> $transportConfigurations
     */
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly UserNotificationRepository $userNotificationRepository,
        #[AutowireLocator(ConfiguratorInterface::DI_TAG)]
        private readonly ServiceLocator $transportConfigurations,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly CompanySelectorInterface $companySelector,
        private readonly CompanyRepository $companyRepository,
        #[Autowire(param: 'solidworx_platform.security.email_verification.enabled')]
        private readonly bool $emailVerificationEnabled,
    ) {
    }

    public function sendNotification(NotificationMessage $message): void
    {
        $attributes = new ReflectionObject($message)
            ->getAttributes(AsNotification::class);

        if ($attributes === []) {
            throw new InvalidNotificationMessageException(sprintf(
                'The notification message "%s" must have the %s set.',
                $message::class,
                AsNotification::class,
            ));
        }

        $event = $attributes[0]->getArguments()['name'] ?? null;

        $message->subject($this->translator->trans($message->getSubjectKey(), $message->getSubjectParameters(), 'email'));

        $companyId = $this->companySelector->getCompany();

        // Recipients are a company's users, so there must be one. Without it the company filter is
        // off and the preferences below would be read across every tenant.
        if (! $companyId instanceof Ulid) {
            $this->logger->warning('Notification not sent: no company is selected', ['event' => $event]);

            return;
        }

        // A disabled company sends nothing to anyone. CompanyEnabledFilter already hides it, so a
        // selected company that no longer loads counts as disabled too.
        $company = $this->companyRepository->find($companyId);

        if (! $company instanceof Company || ! $company->isEnabled()) {
            return;
        }

        $recipients = [];

        foreach ($this->userNotificationRepository->findBy(['event' => $event, 'company' => $company]) as $userNotification) {
            $user = $userNotification->getUser();
            $channels = [];

            if ($userNotification->isEmail()) {
                $channels[] = 'email';
            }

            foreach ($userNotification->getTransports() as $transport) {
                $transportConfiguration = $this->transportConfigurations->get($transport->getTransport());
                assert($transportConfiguration instanceof ConfiguratorInterface);

                $channels[] = sprintf(
                    '%s/%s',
                    match ($transportConfiguration::getType()) {
                        'texter' => 'sms',
                        'chatter' => 'chat',
                        default => $transportConfiguration::getType(),
                    },
                    $transport->getId()
                        ->toString(),
                );
            }

            $recipients[$user->getId()->toString()] = [$user, $channels];
        }

        // Email is on by default: a user who never saved a preference for this event gets it by email.
        // Saving the preferences form writes a row for every event, so "off" is a row with no channels.
        foreach ($company->getUsers() as $user) {
            $recipients[$user->getId()->toString()] ??= [$user, ['email']];
        }

        $hasTransportFailure = false;

        foreach ($recipients as [$user, $channels]) {
            if ($channels === [] || ! $this->canReceive($user)) {
                continue;
            }

            $message->channels($channels);

            try {
                $this->notifier->send($message, new Recipient($user->getEmail(), (string) $user->getMobile()));
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Failed to send notification: ' . $e->getMessage(), [
                    'exception' => $e,
                    'event' => $event,
                ]);

                $hasTransportFailure = true;
            }
        }

        if ($hasTransportFailure) {
            $this->addFlashError('notification.send_failed');
        }
    }

    /**
     * Disabled users, and users who have not verified their email address, get nothing on any channel.
     * Same rule as Platform's EmailVerificationGate, read off the user rather than the logged-in one.
     */
    private function canReceive(User $user): bool
    {
        return $user->isEnabled() && (! $this->emailVerificationEnabled || $user->isVerified());
    }
}
