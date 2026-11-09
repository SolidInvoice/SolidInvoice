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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
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

#[Group('functional')]
final class StartTrialActionTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    private const string URL = '/billing/subscription/trial';

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
     * The action reads the subscription through SubscriptionService, which is
     * off unless SaaS is, so the kernel must boot as SaaS.
     */
    #[After]
    public function resetPlatform(): void
    {
        unset($_SERVER['SOLIDINVOICE_PLATFORM'], $_ENV['SOLIDINVOICE_PLATFORM']);
    }

    /**
     * The trial runs on the cheapest plan with the feature, and every word of
     * the offer comes from the plan rows.
     */
    public function testTheOfferIsForTheCheapestPlanWithTheFeature(): void
    {
        $this->boot($this->subscription('free', SubscriptionStatus::ACTIVE));

        $this->client->request(Request::METHOD_GET, self::URL . '?feature=automated_reminders');

        self::assertResponseIsSuccessful((string) $this->client->getResponse()->getContent());
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Try Solo free for 14 days', $content);
        self::assertStringContainsString('When it ends you go back to Free and keep everything.', $content);
    }

    public function testStartingTheTrialSwitchesThePlanAndReturnsToThePage(): void
    {
        $subscription = $this->subscription('free', SubscriptionStatus::ACTIVE);
        $this->boot($subscription);

        $this->client->request(Request::METHOD_GET, self::URL . '?feature=automated_reminders&return=/invoices');
        $this->client->submitForm('Start my Solo trial');

        self::assertResponseRedirects('/invoices');

        $subscription = $this->reload($subscription);
        self::assertSame(SubscriptionStatus::TRIAL, $subscription->getStatus());
        self::assertSame('solo', $subscription->getPlan()->getPlanId());
        self::assertEqualsWithDelta(CarbonImmutable::now()->addDays(14)->getTimestamp(), $subscription->getEndDate()->getTimestamp(), 60);
        self::assertNotNull($this->em->getRepository(Trial::class)->findOneBy(['user' => $this->user]));
    }

    /**
     * One trial per user, ever. Here it ran on this subscription, which is back
     * on Free.
     */
    public function testAUserWhoHadATrialIsSentToThePlans(): void
    {
        $subscription = $this->subscription('free', SubscriptionStatus::ACTIVE);
        $this->boot($subscription);
        $this->em->persist(Trial::create($this->user, $subscription));
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, self::URL . '?feature=automated_reminders');

        self::assertResponseRedirects('/billing/subscription/plans');
    }

    /**
     * A running trial moves up to the plan the new feature needs, and keeps its
     * end date: moving up widens the trial, it never lengthens it.
     */
    public function testARunningTrialMovesUpAndKeepsItsEndDate(): void
    {
        $subscription = $this->subscription('solo', SubscriptionStatus::TRIAL);
        $endDate = $subscription->getEndDate();
        $this->boot($subscription);
        $this->em->persist(Trial::create($this->user, $subscription));
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, self::URL . '?feature=custom_branding');
        self::assertStringContainsString('Add Business to your trial', (string) $this->client->getResponse()->getContent());

        $this->client->submitForm('Move my trial to Business');

        $subscription = $this->reload($subscription);
        self::assertSame(SubscriptionStatus::TRIAL, $subscription->getStatus());
        self::assertSame('business', $subscription->getPlan()->getPlanId());
        self::assertSame($endDate->getTimestamp(), $subscription->getEndDate()->getTimestamp());
    }

    public function testAnOffSiteReturnPathFallsBackToTheDashboardAndTheStartIsTracked(): void
    {
        $this->boot($this->subscription('free', SubscriptionStatus::ACTIVE));

        // Browsers strip the tab, so "/\t/evil.test" would land on //evil.test.
        $this->client->request(Request::METHOD_GET, self::URL . '?feature=automated_reminders&return=' . rawurlencode("/\t/evil.test/x"));
        $this->client->submitForm('Start my Solo trial');

        self::assertResponseRedirects('/dashboard');

        $this->client->followRedirects();
        $this->client->request(Request::METHOD_GET, '/dashboard');
        self::assertStringContainsString('"event":"trial_start","feature":"automated_reminders","plan":"solo"', (string) $this->client->getResponse()->getContent());
    }

    /**
     * Every way a browser can read a path as another host falls back.
     */
    #[DataProvider('offSiteReturns')]
    public function testAnOffSiteReturnPathFallsBackToTheDashboard(string $return): void
    {
        $this->boot($this->subscription('free', SubscriptionStatus::ACTIVE));

        $this->client->request(Request::METHOD_GET, self::URL . '?feature=automated_reminders&return=' . rawurlencode($return));
        $this->client->submitForm('Start my Solo trial');

        self::assertResponseRedirects('/dashboard');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function offSiteReturns(): iterable
    {
        yield 'protocol-relative' => ['//evil.test/x'];
        yield 'backslash' => ['/\\evil.test/x'];
        yield 'absolute' => ['https://evil.test/x'];
        yield 'scheme' => ['javascript:alert(1)'];
    }

    /**
     * A forged POST without the token starts nothing, and keeps the user's one
     * trial unused.
     */
    public function testAPostWithoutAValidTokenStartsNothing(): void
    {
        $subscription = $this->subscription('free', SubscriptionStatus::ACTIVE);
        $this->boot($subscription);

        $this->client->request(Request::METHOD_POST, self::URL, ['feature' => 'automated_reminders', '_token' => 'forged']);

        self::assertResponseRedirects('/billing/subscription/plans');
        self::assertSame(SubscriptionStatus::ACTIVE, $this->reload($subscription)->getStatus());
        self::assertNull($this->em->getRepository(Trial::class)->findOneBy(['user' => $this->user]));
    }

    /**
     * The pricing page names a plan instead of a feature.
     */
    public function testAPlanLinkStartsATrialOnThatPlan(): void
    {
        $subscription = $this->subscription('free', SubscriptionStatus::ACTIVE);
        $this->boot($subscription);

        $this->client->request(Request::METHOD_GET, self::URL . '?plan=business');
        $this->client->submitForm('Start my Business trial');

        $subscription = $this->reload($subscription);
        self::assertSame(SubscriptionStatus::TRIAL, $subscription->getStatus());
        self::assertSame('business', $subscription->getPlan()->getPlanId());
    }

    public function testALinkWithNothingToOfferGoesToThePlans(): void
    {
        $this->boot($this->subscription('free', SubscriptionStatus::ACTIVE));

        $this->client->request(Request::METHOD_GET, self::URL);

        self::assertResponseRedirects('/billing/subscription/plans');
    }

    /**
     * Each request resets the EntityManager, so the test's copy is detached.
     */
    private function reload(Subscription $subscription): Subscription
    {
        $this->em->clear();
        $fresh = $this->em->find(Subscription::class, $subscription->getId());
        self::assertInstanceOf(Subscription::class, $fresh);

        return $fresh;
    }

    private function boot(Subscription $subscription): void
    {
        $this->em->persist($subscription);
        $this->em->flush();

        $user = UserFactory::createOne(['companies' => [$this->company], 'verified' => true]);
        self::assertInstanceOf(User::class, $user);
        $this->user = $user;

        $this->client->loginUser($user);
    }

    private function subscription(string $plan, SubscriptionStatus $status): Subscription
    {
        $this->seed();

        return new Subscription()
            ->setSubscriber($this->company)
            ->setPlan($this->plans[$plan])
            ->setStatus($status)
            ->setStartDate(CarbonImmutable::now()->subDay())
            ->setEndDate($status === SubscriptionStatus::TRIAL ? CarbonImmutable::now()->addDays(5) : CarbonImmutable::now()->addYears(100));
    }

    /**
     * Free, then Solo (reminders, 14-day trial) and Business (branding too, 30
     * days). Business also has reminders, so "cheapest" is what picks Solo.
     */
    private function seed(): void
    {
        if ($this->plans !== []) {
            return;
        }

        $_SERVER['SOLIDINVOICE_PLATFORM'] = $_ENV['SOLIDINVOICE_PLATFORM'] = 'saas';

        self::ensureKernelShutdown();
        $this->client = self::createClient();
        $this->client->disableReboot();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // The reboot above leaves $this->company unknown to the new EntityManager.
        $company = $this->em->getRepository(Company::class)->find($this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        foreach ([['Free', '0', 0, null], ['Solo', 'solo', 900, 'P14D'], ['Business', 'business', 3900, 'P30D']] as [$name, $planId, $price, $trial]) {
            $plan = new Plan()->setName($name)->setPlanId($planId)->setPrice($price)->setActive(true)->setDefault($price === 0);

            if ($trial !== null) {
                $plan->setTrialDuration(new DateInterval($trial));
            }

            $this->em->persist($plan);
            $this->plans[$planId] = $plan;
        }

        $this->plans['free'] = $this->plans['0'];

        foreach ([['solo', Feature::AutomatedReminders], ['business', Feature::AutomatedReminders], ['business', Feature::CustomBranding]] as [$planId, $feature]) {
            $this->em->persist(new PlanFeature()
                ->setPlan($this->plans[$planId])
                ->setFeatureKey($feature->value)
                ->setType($feature->getType())
                ->setValue(true));
        }

        $this->em->flush();
    }
}
