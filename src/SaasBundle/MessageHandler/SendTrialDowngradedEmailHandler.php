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

namespace SolidInvoice\SaasBundle\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Email\TrialDowngradedEmail;
use SolidInvoice\SaasBundle\Message\SendTrialDowngradedEmailMessage;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class SendTrialDowngradedEmailHandler
{
    public function __construct(
        private ManagerRegistry $registry,
        private CompanySelector $companySelector,
        private MailerInterface $mailer,
        private SystemConfig $systemConfig,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendTrialDowngradedEmailMessage $message): void
    {
        // Clear tenant scope up front and guarantee it's reset on every exit
        // path so a skipped message never leaves the worker unscoped.
        $this->companySelector->reset();

        try {
            $manager = $this->registry->getManagerForClass(Subscription::class);
            $subscription = $manager?->find(Subscription::class, $message->subscriptionId);

            if (! $subscription instanceof Subscription) {
                $this->logger->warning('Trial-downgraded email skipped: subscription not found', [
                    'subscription_id' => $message->subscriptionId->toBase58(),
                ]);
                return;
            }

            $trial = $manager->getRepository(Trial::class)->findOneBy(['subscription' => $subscription]);

            if (! $trial instanceof Trial) {
                $this->logger->warning('Trial-downgraded email skipped: no trial for subscription', [
                    'subscription_id' => $message->subscriptionId->toBase58(),
                ]);
                return;
            }

            $user = $trial->getUser();

            if (! $user instanceof User) {
                $this->logger->warning('Trial-downgraded email skipped: trial owner is not a User', [
                    'subscription_id' => $message->subscriptionId->toBase58(),
                ]);
                return;
            }

            $company = $subscription->getSubscriber();

            if (! $company instanceof Company) {
                $this->logger->warning('Trial-downgraded email skipped: subscriber is not a Company', [
                    'subscription_id' => $message->subscriptionId->toBase58(),
                ]);
                return;
            }

            $this->companySelector->switchCompany($company->getId());

            $email = new TrialDowngradedEmail($user, $company, $this->translator);

            $fromAddress = $this->systemConfig->get('email/from_address');
            $fromName = $this->systemConfig->get('email/from_name');

            if ($fromAddress !== null && $fromAddress !== '') {
                $email->from(new Address($fromAddress, $fromName ?? ''));
            }

            try {
                $this->mailer->send($email);

                $this->logger->info('Sent trial-downgraded email', [
                    'subscription_id' => $message->subscriptionId->toBase58(),
                    'user_id' => $user->getId()?->toString(),
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Failed to send trial-downgraded email', [
                    'subscription_id' => $message->subscriptionId->toBase58(),
                    'exception' => $e->getMessage(),
                ]);

                // Re-throw so Messenger retries with its exponential backoff.
                throw $e;
            }
        } finally {
            $this->companySelector->reset();
        }
    }
}
