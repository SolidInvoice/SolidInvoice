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

namespace SolidInvoice\DashboardBundle\Widgets;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\UserBundle\Affiliate\AffiliateProgram;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * The quiet "Refer & earn" card, plus the one-off corner prompt when the user is eligible for it.
 *
 * @see \SolidInvoice\DashboardBundle\Tests\Functional\AffiliatePromptTest
 */
final readonly class AffiliateProgramWidget implements WidgetInterface
{
    public function __construct(
        private AffiliateProgram $affiliateProgram,
        private Security $security,
        private Telemetry $telemetry,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        if (! $this->affiliateProgram->isEnabled()) {
            return ['show' => false];
        }

        $user = $this->security->getUser();

        try {
            $showPrompt = $user instanceof User && $this->affiliateProgram->shouldShowPrompt($user);
        } catch (DBALException | ORMException $e) {
            // An optional prompt is not worth an error state; try again on the next visit.
            $this->logger->error('Unable to decide whether to show the affiliate prompt', ['exception' => $e]);
            $showPrompt = false;
        }

        if ($showPrompt) {
            $this->telemetry->event(TelemetryEvent::AffiliatePromptShown);
        }

        return [
            'show' => true,
            'showPrompt' => $showPrompt,
            'yearlyEarnings' => $this->affiliateProgram->getYearlyEarnings(),
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/affiliate_program.html.twig';
    }
}
