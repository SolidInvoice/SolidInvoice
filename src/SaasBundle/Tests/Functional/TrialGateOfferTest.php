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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Carbon\CarbonImmutable;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\Test\SaasKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\PlanFeature;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Twig\Environment;

/**
 * Every gate offers the trial through `trial_offer()`: start one, move a running
 * one up, or fall back to "Upgrade" once the user has had their trial.
 *
 * Plans: Free has neither feature, Solo (14-day trial) has recurring invoices,
 * Business (30-day trial) has recurring invoices and quotes. Clients are
 * limited to 3, 10 and 100.
 */
#[Group('functional')]
final class TrialGateOfferTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private User $user;

    /**
     * @var array<string, Plan>
     */
    private array $plans = [];

    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasKernel::class;
    }

    /**
     * `trial_offer()` is a no-op in self-hosted builds, which register the Core
     * stub, so the kernel must boot as SaaS.
     */
    #[After]
    public function resetPlatform(): void
    {
        unset($_SERVER['SOLIDINVOICE_PLATFORM'], $_ENV['SOLIDINVOICE_PLATFORM']);
    }

    public function testAGateOffersATrialOnTheCheapestPlanWithTheFeature(): void
    {
        $this->boot('free', SubscriptionStatus::ACTIVE);

        $this->client->request(Request::METHOD_GET, '/invoices/recurring/create');

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Try Solo free for 14 days', $content);
        self::assertStringContainsString('/billing/subscription/trial?feature=recurring_invoices&amp;return=/invoices/recurring/create', $content);
        self::assertStringNotContainsString('Upgrade to Solo', $content);
    }

    /**
     * A limit counts as "enabled" while it is above zero, so the gate a Free
     * user hits at their 3rd client must still offer the trial.
     */
    public function testALimitGateOffersTheTrial(): void
    {
        $this->boot('free', SubscriptionStatus::ACTIVE);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
        ClientFactory::createMany(3, ['company' => $this->company, 'archived' => null]);

        $this->client->request(Request::METHOD_GET, '/clients/add');

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Try Solo free for 14 days', $content);
        self::assertStringContainsString('/billing/subscription/trial?feature=total_clients', $content);
    }

    public function testAUserWhoHadTheirTrialIsOfferedTheUpgrade(): void
    {
        $subscription = $this->boot('free', SubscriptionStatus::ACTIVE);
        $this->em->persist(Trial::create($this->user, $subscription));
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/invoices/recurring/create');

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Upgrade to Solo', $content);
        self::assertStringNotContainsString('/billing/subscription/trial', $content);
    }

    /**
     * Solo lacks quotes, so during a Solo trial the quotes gate moves the trial
     * up to Business.
     */
    public function testARunningTrialIsOfferedTheMoveUp(): void
    {
        $subscription = $this->boot('solo', SubscriptionStatus::TRIAL);
        $this->em->persist(Trial::create($this->user, $subscription));
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/quotes/create');

        self::assertStringContainsString('Add Business to your trial', (string) $this->client->getResponse()->getContent());
    }

    /**
     * The settings badge for a locked feature links to the trial too.
     */
    public function testTheSettingsBadgeOffersTheTrial(): void
    {
        $this->boot('free', SubscriptionStatus::ACTIVE);

        $container = self::getContainer();
        $container->get('security.token_storage')->setToken(new UsernamePasswordToken($this->user, 'main', $this->user->getRoles()));
        $container->get(CompanySelector::class)->switchCompany($this->company->getId());

        $html = $container->get(Environment::class)->createTemplate(
            "{% import '@SolidInvoiceSettings/Form/fields.html.twig' as upgrade %}{{ upgrade.feature_gated_badge('Solo', 'recurring_invoices') }}",
        )->render();

        self::assertStringContainsString('Try Solo free', (string) $html);
        self::assertStringContainsString('/billing/subscription/trial?feature=recurring_invoices', (string) $html);
    }

    private function boot(string $plan, SubscriptionStatus $status): Subscription
    {
        $_SERVER['SOLIDINVOICE_PLATFORM'] = $_ENV['SOLIDINVOICE_PLATFORM'] = 'saas';

        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->client->disableReboot();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // The reboot above leaves $this->company unknown to the new EntityManager.
        $company = $this->em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        $this->seedPlans();

        $subscription = new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($this->plans[$plan])
            ->setStatus($status)
            ->setStartDate(CarbonImmutable::now()->subDay())
            ->setEndDate($status === SubscriptionStatus::TRIAL ? CarbonImmutable::now()->addDays(5) : CarbonImmutable::now()->addYears(100));
        $this->em->persist($subscription);
        $this->em->flush();

        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);
        $this->user = $user;
        $this->client->loginUser($user);

        return $subscription;
    }

    private function seedPlans(): void
    {
        $features = [
            'free' => [Feature::RecurringInvoices->value => false, Feature::Quotes->value => false, Feature::TotalClients->value => 3],
            'solo' => [Feature::RecurringInvoices->value => true, Feature::Quotes->value => false, Feature::TotalClients->value => 10],
            'business' => [Feature::RecurringInvoices->value => true, Feature::Quotes->value => true, Feature::TotalClients->value => 100],
        ];

        foreach ([['free', 'Free', '0', 0, null], ['solo', 'Solo', 'solo', 900, 'P14D'], ['business', 'Business', 'business', 3900, 'P30D']] as [$key, $name, $planId, $price, $trial]) {
            $plan = new Plan()->setName($name)->setPlanId($planId)->setPrice($price)->setActive(true)->setDefault($price === 0);

            if ($trial !== null) {
                $plan->setTrialDuration(new DateInterval($trial));
            }

            $this->em->persist($plan);
            $this->plans[$key] = $plan;

            foreach ($features[$key] as $featureKey => $enabled) {
                $this->em->persist(new PlanFeature()
                    ->setPlan($plan)
                    ->setFeatureKey($featureKey)
                    ->setType(Feature::from($featureKey)->getType())
                    ->setValue($enabled));
            }
        }
    }
}
