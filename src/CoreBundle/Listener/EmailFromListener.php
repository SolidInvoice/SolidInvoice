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

namespace SolidInvoice\CoreBundle\Listener;

use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Contracts\ClientEmailInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Listener\EmailFromListenerTest
 */
final readonly class EmailFromListener implements EventSubscriberInterface
{
    public function __construct(
        private SystemConfig $config,
        private TokenStorageInterface $tokenStorage,
        private CompanySelectorInterface $companySelector,
        #[Autowire(env: 'SOLIDINVOICE_MAILER_SENDER')]
        private string $defaultSender,
    ) {
    }

    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (! $message instanceof Email) {
            return;
        }

        // Without a company (e.g. the 2FA code mail sent mid-login) the company
        // settings can't be resolved, so send from the system sender instead.
        if (! $this->companySelector->getCompany() instanceof Ulid) {
            $from = Address::create($this->defaultSender);
            $message->from($from);
            $event->getEnvelope()->setSender($from);
            $message->getHeaders()->remove('Sender');

            return;
        }

        $fromAddress = (string) $this->config->get('email/from_address');

        if ('' !== $fromAddress) {
            $fromName = (string) $this->config->get('email/from_name');
            $from = new Address($fromAddress, $fromName);
            $message->from($from);
            $event->getEnvelope()->setSender($from);
            $message->getHeaders()->remove('Sender');
        } elseif (null !== ($userEmail = $this->currentUserEmail())) {
            $from = Address::create($userEmail);
            $message->from($from);
            $event->getEnvelope()->setSender($from);
            $message->getHeaders()->remove('Sender');
        }

        // A client replying to an invoice or quote should reach the business, not the no-reply sender.
        if ($message instanceof ClientEmailInterface && [] === $message->getReplyTo()) {
            $replyTo = (string) $this->config->get('system/company/contact_details/email');
            $replyTo = '' !== $replyTo ? $replyTo : $this->currentUserEmail();

            if (null !== $replyTo) {
                $message->replyTo($replyTo);
            }
        }
    }

    private function currentUserEmail(): ?string
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof User ? $user->getEmail() : null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => ['__invoke', -256],
        ];
    }
}
