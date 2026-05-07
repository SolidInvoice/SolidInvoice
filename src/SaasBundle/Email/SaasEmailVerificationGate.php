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

namespace SolidInvoice\SaasBundle\Email;

use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaasEmailVerificationGate implements EmailVerificationGateInterface, ResetInterface
{
    private ?bool $cachedIsGated = null;

    /**
     * @var array<string, bool>
     */
    private array $companyCache = [];

    public function __construct(
        private readonly Security $security,
        private readonly CompanySelectorInterface $companySelector,
        private readonly CompanyRepository $companyRepository,
        private readonly SubscriptionProviderInterface $subscriptionProvider,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function isGated(): bool
    {
        if ($this->cachedIsGated !== null) {
            return $this->cachedIsGated;
        }

        $user = $this->security->getUser();

        if (! $user instanceof User || $user->isVerified()) {
            return $this->cachedIsGated = false;
        }

        $companyId = $this->companySelector->getCompany();
        if (! $companyId instanceof Ulid) {
            return $this->cachedIsGated = false;
        }

        $company = $this->companyRepository->find($companyId);
        if (! $company instanceof Company) {
            return $this->cachedIsGated = false;
        }

        $subscription = $this->subscriptionProvider->getSubscriptionFor($company);

        return $this->cachedIsGated = $subscription instanceof Subscription;
    }

    public function isCompanyGated(Company $company): bool
    {
        $key = (string) $company->getId();
        if (isset($this->companyCache[$key])) {
            return $this->companyCache[$key];
        }

        $subscription = $this->subscriptionProvider->getSubscriptionFor($company);
        if (! $subscription instanceof Subscription) {
            return $this->companyCache[$key] = false;
        }

        foreach ($company->getUsers() as $user) {
            if ($user instanceof User && $user->isVerified()) {
                return $this->companyCache[$key] = false;
            }
        }

        return $this->companyCache[$key] = true;
    }

    public function reason(string $action): string
    {
        return $this->translator->trans(
            'email_verification.gate.reason',
            ['%action%' => $action],
        );
    }

    public function reset(): void
    {
        $this->cachedIsGated = null;
        $this->companyCache = [];
    }
}
