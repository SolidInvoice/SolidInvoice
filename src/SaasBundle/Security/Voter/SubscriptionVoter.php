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

namespace SolidInvoice\SaasBundle\Security\Voter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SolidInvoice\ApiBundle\Security\Attribute as ApiAttribute;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\McpBundle\Security\Attribute as McpAttribute;
use SolidInvoice\SaasBundle\Service\SubscriptionEligibility;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Uid\Ulid;
use Throwable;

/**
 * Subscription-aware voter for the MCP and API access attributes.
 *
 * Only registered on SaaS deployments (this bundle is conditionally loaded
 * via `SOLIDINVOICE_PLATFORM=saas`), so the `saas_enabled` toggle does not
 * need to be re-checked here — its presence in the voter list already
 * implies the SaaS platform is active. The per-bundle access voters
 * (`McpAccessVoter`, `ApiAccessVoter`) handle self-hosted installs.
 */
final class SubscriptionVoter extends Voter
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly SubscriptionEligibility $eligibility,
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === McpAttribute::ACCESS || $attribute === ApiAttribute::ACCESS;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        try {
            // Without a resolved company we can't check the subscription state. Fail closed
            // so a misconfigured CompanySelector can't accidentally expose cross-tenant data.
            $companyId = $this->companySelector->getCompany();
            if (! $companyId instanceof Ulid) {
                return $this->deny($vote, 'No company is associated with this request.');
            }

            $company = $this->companyRepository->find($companyId);
            if ($company === null) {
                return $this->deny($vote, 'No company is associated with this request.');
            }

            $result = $this->eligibility->evaluate($company);

            if ($result->active) {
                return true;
            }

            return $this->deny($vote, $result->reason ?? 'Your subscription is not currently active.');
        } catch (Throwable $e) {
            // Self-hosted dev/test environments may not have the SaaS schema present even
            // when this voter is registered. Log and grant so authentication still works
            // — production SaaS deployments will surface the failure via the logger.
            $this->logger->warning(
                'SubscriptionVoter failed to evaluate access; granting by default.',
                ['exception' => $e],
            );

            return true;
        }
    }

    private function deny(?Vote $vote, string $reason): bool
    {
        $vote?->addReason($reason);

        return false;
    }
}
