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

namespace SolidInvoice\CoreBundle\Command;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use function array_sum;
use function count;
use function max;
use function min;
use function number_format;
use function round;
use function sprintf;
use function str_repeat;

#[AsCommand(name: 'test', description: 'Test')]
final class TestCommand extends Command
{
    private readonly Connection $connection;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
        parent::__construct();
        /** @var Connection $conn */
        $conn = $this->registry->getConnection();
        $this->connection = $conn;
    }

    protected function handle(): int
    {
        $this->io->title('SolidInvoice Usage Analytics');

        $this->io->writeln('  Gathering data...');

        $stats = $this->gatherStats();

        $this->displayFunnel($stats);
        $this->displayOnboardingAnalysis($stats);
        $this->displayCompanyMetrics($stats);
        $this->displayEngagement($stats);
        $this->displayDropOffAnalysis($stats);

        return self::SUCCESS;
    }

    /**
     * @return array<string, int|float>
     */
    private function gatherStats(): array
    {
        $conn = $this->connection;

        // Users
        $totalUsers = (int) ($conn->fetchOne('SELECT COUNT(*) FROM users') ?: 0);

        // Companies
        $totalCompanies = (int) ($conn->fetchOne('SELECT COUNT(*) FROM companies') ?: 0);

        // Users who created at least one company
        $usersWithCompany = (int) ($conn->fetchOne('SELECT COUNT(DISTINCT user_id) FROM user_company') ?: 0);

        // Onboarding state from user_settings
        $onboardStarted = (int) ($conn->fetchOne(
            "SELECT COUNT(*) FROM user_settings WHERE setting_key = 'onboarding_started_at'"
        ) ?: 0);

        $onboardCompleted = (int) ($conn->fetchOne(
            "SELECT COUNT(*) FROM user_settings WHERE setting_key = 'onboard_complete' AND setting_value = 'true'"
        ) ?: 0);

        $onboardDismissed = (int) ($conn->fetchOne(
            "SELECT COUNT(*) FROM user_settings WHERE setting_key = 'onboard_complete' AND setting_value = 'dismissed'"
        ) ?: 0);

        // Skipped steps — stored as JSON array e.g. ["client"] or ["client","invoice"]
        $skippedClient = (int) ($conn->fetchOne(
            "SELECT COUNT(*) FROM user_settings WHERE setting_key = 'onboarding_skipped' AND setting_value LIKE '%\"client\"%'"
        ) ?: 0);

        $skippedInvoice = (int) ($conn->fetchOne(
            "SELECT COUNT(*) FROM user_settings WHERE setting_key = 'onboarding_skipped' AND setting_value LIKE '%\"invoice\"%'"
        ) ?: 0);

        // Dashboard checklist dismissed
        $checklistDismissed = (int) ($conn->fetchOne(
            "SELECT COUNT(*) FROM user_settings WHERE setting_key = 'onboarding_checklist_dismissed' AND setting_value = 'true'"
        ) ?: 0);

        // Clients
        $companiesWithClients = (int) ($conn->fetchOne(
            'SELECT COUNT(DISTINCT company_id) FROM clients'
        ) ?: 0);

        $avgClientsPerCompany = (float) ($conn->fetchOne(
            'SELECT AVG(c) FROM (SELECT COUNT(*) AS c FROM clients GROUP BY company_id) AS t'
        ) ?: 0.0);

        // Invoices
        $companiesWithInvoices = (int) ($conn->fetchOne(
            'SELECT COUNT(DISTINCT company_id) FROM invoices'
        ) ?: 0);

        // "Sent" = any status other than draft (pending, paid, overdue, active, etc.)
        $companiesWithSentInvoices = (int) ($conn->fetchOne(
            "SELECT COUNT(DISTINCT company_id) FROM invoices WHERE status != 'draft'"
        ) ?: 0);

        $avgInvoicesPerCompany = (float) ($conn->fetchOne(
            'SELECT AVG(c) FROM (SELECT COUNT(*) AS c FROM invoices GROUP BY company_id) AS t'
        ) ?: 0.0);

        // Payment gateways — internal=0 means a real online gateway (Stripe, PayPal, etc.)
        $companiesWithGateway = (int) ($conn->fetchOne(
            'SELECT COUNT(DISTINCT company_id) FROM payment_methods WHERE internal = 0 AND enabled = 1'
        ) ?: 0);

        // Logo — stored in app_config as key 'system/company/logo'
        $companiesWithLogo = (int) ($conn->fetchOne(
            "SELECT COUNT(DISTINCT company_id) FROM app_config WHERE setting_key = 'system/company/logo' AND setting_value IS NOT NULL AND setting_value != ''"
        ) ?: 0);

        // Average days between registration and last login (computed in PHP for DB portability)
        $loginRows = $conn->fetchAllAssociative(
            'SELECT created, last_login FROM users WHERE last_login IS NOT NULL AND created IS NOT NULL'
        );

        $diffs = [];
        foreach ($loginRows as $row) {
            $created = new DateTimeImmutable((string) $row['created']);
            $lastLogin = new DateTimeImmutable((string) $row['last_login']);
            $diffs[] = $lastLogin->diff($created)->days;
        }

        $avgDaysUntilLastLogin = $diffs !== [] ? array_sum($diffs) / count($diffs) : 0.0;

        return [
            'totalUsers' => $totalUsers,
            'totalCompanies' => $totalCompanies,
            'usersWithCompany' => $usersWithCompany,
            'onboardStarted' => $onboardStarted,
            'onboardCompleted' => $onboardCompleted,
            'onboardDismissed' => $onboardDismissed,
            'skippedClient' => $skippedClient,
            'skippedInvoice' => $skippedInvoice,
            'checklistDismissed' => $checklistDismissed,
            'companiesWithClients' => $companiesWithClients,
            'avgClientsPerCompany' => $avgClientsPerCompany,
            'companiesWithInvoices' => $companiesWithInvoices,
            'companiesWithSentInvoices' => $companiesWithSentInvoices,
            'avgInvoicesPerCompany' => $avgInvoicesPerCompany,
            'companiesWithGateway' => $companiesWithGateway,
            'companiesWithLogo' => $companiesWithLogo,
            'avgDaysUntilLastLogin' => $avgDaysUntilLastLogin,
        ];
    }

    /**
     * Conversion funnel — top-level view of user journey from registration through activation.
     *
     * @param array<string, int|float> $stats
     */
    private function displayFunnel(array $stats): void
    {
        $this->io->section('Conversion Funnel');

        $total = (int) $stats['totalUsers'];
        $companies = (int) $stats['totalCompanies'];

        $rows = [
            ['Total users registered',            $this->n($total),                                    '100.0%'],
            ['  Started onboarding',              $this->n($stats['onboardStarted']),                  $this->pct($stats['onboardStarted'], $total)],
            ['    Created company',               $this->n($stats['usersWithCompany']),                $this->pct($stats['usersWithCompany'], $total)],
            ['      Added a client',              $this->n($stats['companiesWithClients']),            $this->pct($stats['companiesWithClients'], $companies)],
            ['        Created an invoice',        $this->n($stats['companiesWithInvoices']),           $this->pct($stats['companiesWithInvoices'], $companies)],
            ['          Sent an invoice',         $this->n($stats['companiesWithSentInvoices']),       $this->pct($stats['companiesWithSentInvoices'], $companies)],
            ['            Added payment gateway', $this->n($stats['companiesWithGateway']),            $this->pct($stats['companiesWithGateway'], $companies)],
            ['            Uploaded logo',         $this->n($stats['companiesWithLogo']),               $this->pct($stats['companiesWithLogo'], $companies)],
        ];

        $this->io->table(['Stage', 'Count', '% of Baseline'], $rows);
        $this->io->note('Company-level metrics (below "Created company") use total companies as their baseline.');
    }

    /**
     * Onboarding flow breakdown — which steps users skip or abandon.
     *
     * @param array<string, int|float> $stats
     */
    private function displayOnboardingAnalysis(array $stats): void
    {
        $this->io->section('Onboarding Flow Breakdown');

        $started = (int) $stats['onboardStarted'];
        $total = (int) $stats['totalUsers'];

        $rows = [
            ['Completed full onboarding',    $this->n($stats['onboardCompleted']),   $this->pct($stats['onboardCompleted'], $started),   '% of started'],
            ['Dismissed onboarding',         $this->n($stats['onboardDismissed']),   $this->pct($stats['onboardDismissed'], $started),    '% of started'],
            ['Skipped "client" step',        $this->n($stats['skippedClient']),      $this->pct($stats['skippedClient'], $started),       '% of started'],
            ['Skipped "invoice" step',       $this->n($stats['skippedInvoice']),     $this->pct($stats['skippedInvoice'], $started),      '% of started'],
            ['Dismissed dashboard checklist', $this->n($stats['checklistDismissed']), $this->pct($stats['checklistDismissed'], $total),    '% of all users'],
        ];

        $this->io->table(['Metric', 'Count', 'Rate', 'Denominator'], $rows);
    }

    /**
     * Per-company feature adoption rates and averages.
     *
     * @param array<string, int|float> $stats
     */
    private function displayCompanyMetrics(array $stats): void
    {
        $this->io->section('Company Feature Adoption');

        $companies = (int) $stats['totalCompanies'];

        $rows = [
            ['Has at least 1 client',         $this->n($stats['companiesWithClients']) . ' / ' . $this->n($companies), $this->pct($stats['companiesWithClients'], $companies)],
            ['  Avg clients per company',      number_format($stats['avgClientsPerCompany'], 1),                              '-'],
            ['Has at least 1 invoice',         $this->n($stats['companiesWithInvoices']) . ' / ' . $this->n($companies), $this->pct($stats['companiesWithInvoices'], $companies)],
            ['  Avg invoices per company',     number_format($stats['avgInvoicesPerCompany'], 1),                             '-'],
            ['Sent at least 1 invoice',        $this->n($stats['companiesWithSentInvoices']) . ' / ' . $this->n($companies), $this->pct($stats['companiesWithSentInvoices'], $companies)],
            ['Configured payment gateway',     $this->n($stats['companiesWithGateway']) . ' / ' . $this->n($companies), $this->pct($stats['companiesWithGateway'], $companies)],
            ['Uploaded company logo',          $this->n($stats['companiesWithLogo']) . ' / ' . $this->n($companies), $this->pct($stats['companiesWithLogo'], $companies)],
        ];

        $this->io->table(['Metric', 'Value', '%'], $rows);
    }

    /**
     * User engagement longevity metric.
     *
     * @param array<string, int|float> $stats
     */
    private function displayEngagement(array $stats): void
    {
        $this->io->section('User Engagement');

        $avg = $stats['avgDaysUntilLastLogin'];

        $this->io->table(
            ['Metric', 'Value'],
            [
                [
                    'Avg days between registration and last login',
                    number_format($avg, 1) . ' days' . ($avg < 1 ? ' (most users only logged in once)' : ''),
                ],
            ]
        );
    }

    /**
     * Where users most commonly stop progressing — the friction points.
     *
     * @param array<string, int|float> $stats
     */
    private function displayDropOffAnalysis(array $stats): void
    {
        $this->io->section('Drop-off Analysis (Friction Points)');

        $stages = [
            [
                'label' => 'Registered but never started onboarding',
                'dropped' => (int) $stats['totalUsers'] - (int) $stats['onboardStarted'],
                'from' => (int) $stats['totalUsers'],
            ],
            [
                'label' => 'Started onboarding but dismissed it',
                'dropped' => (int) $stats['onboardDismissed'],
                'from' => (int) $stats['onboardStarted'],
            ],
            [
                'label' => 'Created company but never added a client',
                'dropped' => (int) $stats['totalCompanies'] - (int) $stats['companiesWithClients'],
                'from' => (int) $stats['totalCompanies'],
            ],
            [
                'label' => 'Added client but never created an invoice',
                'dropped' => (int) $stats['companiesWithClients'] - (int) $stats['companiesWithInvoices'],
                'from' => (int) $stats['companiesWithClients'],
            ],
            [
                'label' => 'Created invoice but never sent it',
                'dropped' => (int) $stats['companiesWithInvoices'] - (int) $stats['companiesWithSentInvoices'],
                'from' => (int) $stats['companiesWithInvoices'],
            ],
            [
                'label' => 'Active but no payment gateway configured',
                'dropped' => (int) $stats['totalCompanies'] - (int) $stats['companiesWithGateway'],
                'from' => (int) $stats['totalCompanies'],
            ],
            [
                'label' => 'Active but no logo uploaded',
                'dropped' => (int) $stats['totalCompanies'] - (int) $stats['companiesWithLogo'],
                'from' => (int) $stats['totalCompanies'],
            ],
        ];

        $rows = [];
        $biggest = ['label' => '', 'pct' => 0.0];

        foreach ($stages as $stage) {
            $dropped = max(0, $stage['dropped']);
            $pct = $stage['from'] > 0 ? ($dropped / $stage['from']) * 100 : 0.0;
            $rows[] = [$stage['label'], $this->n($dropped), sprintf('%5.1f%%', $pct), $this->bar($pct)];

            if ($pct > $biggest['pct']) {
                $biggest = ['label' => $stage['label'], 'pct' => $pct];
            }
        }

        $this->io->table(['Friction Point', 'Lost', 'Drop %', 'Visual (100% = full bar)'], $rows);

        if ($biggest['pct'] > 0) {
            $this->io->warning(sprintf(
                'Biggest drop-off: "%s" — %.1f%% of users stop here.',
                $biggest['label'],
                $biggest['pct'],
            ));
        }
    }

    private function pct(int | float $value, int $total): string
    {
        if ($total === 0) {
            return '  n/a';
        }

        return sprintf('%5.1f%%', ($value / $total) * 100);
    }

    private function n(int | float $value): string
    {
        return number_format((int) $value);
    }

    private function bar(float $pct, int $width = 20): string
    {
        $filled = min($width, max(0, (int) round(($pct / 100) * $width)));
        return str_repeat('#', $filled) . str_repeat('-', $width - $filled);
    }
}
