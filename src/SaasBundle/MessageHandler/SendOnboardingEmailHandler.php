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

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Onboarding\OnboardingStepRegistry;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final readonly class SendOnboardingEmailHandler
{
    public function __construct(
        private ManagerRegistry $registry,
        private OnboardingStepRegistry $stepRegistry,
        private CompanySelector $companySelector,
        private MailerInterface $mailer,
        private SystemConfig $systemConfig,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendOnboardingEmailMessage $message): void
    {
        // Clear any existing company scope up front, then guarantee it's reset
        // on every exit path (including early returns and exceptions) so a
        // skipped message never leaves the worker with tenant filtering off.
        $this->companySelector->reset();

        try {
            $userManager = $this->registry->getManagerForClass(User::class);
            $user = $userManager?->find(User::class, $message->userId);

            if (! $user instanceof User) {
                $this->logger->warning('Onboarding email skipped: user not found', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                ]);
                return;
            }

            $trialManager = $this->registry->getManagerForClass(Trial::class);
            $trial = $trialManager?->getRepository(Trial::class)->findOneBy(['user' => $user]);

            if (! $trial instanceof Trial) {
                $this->logger->warning('Onboarding email skipped: no trial for user', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                ]);
                return;
            }

            $subscription = $trial->getSubscription();

            if ($subscription->getStatus() !== SubscriptionStatus::TRIAL) {
                // User upgraded, cancelled, or trial expired between dispatch and
                // handling — stop the sequence silently.
                return;
            }

            $company = $subscription->getSubscriber();

            if (! $company instanceof Company) {
                $this->logger->warning('Onboarding email skipped: subscriber is not a Company', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                ]);
                return;
            }

            $step = $this->stepRegistry->get($message->stepKey);

            if ($step === null) {
                $this->logger->warning('Onboarding email skipped: unknown step', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                ]);
                return;
            }

            $this->companySelector->switchCompany($company->getId());

            $context = new OnboardingContext(
                user: $user,
                company: $company,
                subscription: $subscription,
                plan: $subscription->getPlan(),
                trialStart: DateTimeImmutable::createFromInterface($subscription->getStartDate()),
                trialEnd: DateTimeImmutable::createFromInterface($subscription->getEndDate()),
            );

            if (! $step->shouldSend($context)) {
                $this->logger->info('Onboarding email skipped by step condition', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                ]);
                return;
            }

            $email = $step->createEmail($context);

            $email->to(Address::create((string) $user->getEmail()));

            $fromAddress = $this->systemConfig->get('email/from_address');
            $fromName = $this->systemConfig->get('email/from_name');

            if ($fromAddress !== null && $fromAddress !== '') {
                $email->from(new Address($fromAddress, $fromName ?? ''));
            }

            try {
                $this->mailer->send($email);

                $this->logger->info('Sent onboarding email', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                ]);
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Failed to send onboarding email', [
                    'user_id' => $message->userId->toString(),
                    'step_key' => $message->stepKey,
                    'exception' => $e->getMessage(),
                ]);

                // Re-throw so Messenger can retry with its exponential backoff.
                throw $e;
            }
        } finally {
            $this->companySelector->reset();
        }
    }
}
