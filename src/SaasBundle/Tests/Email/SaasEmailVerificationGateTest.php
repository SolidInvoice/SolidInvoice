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

namespace SolidInvoice\SaasBundle\Tests\Email;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\SaasBundle\Email\SaasEmailVerificationGate;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SaasEmailVerificationGateTest extends TestCase
{
    public function testNotGatedWhenAnonymous(): void
    {
        $gate = $this->makeGate(user: null);
        self::assertFalse($gate->isGated());
    }

    public function testNotGatedWhenUserVerified(): void
    {
        $user = (new User())->setVerified(true);
        $gate = $this->makeGate(user: $user);
        self::assertFalse($gate->isGated());
    }

    public function testNotGatedWhenNoSubscription(): void
    {
        $user = (new User())->setVerified(false);
        $gate = $this->makeGate(user: $user, subscription: null);
        self::assertFalse($gate->isGated());
    }

    public function testGatedWhenUnverifiedAndSubscribed(): void
    {
        $user = (new User())->setVerified(false);
        $gate = $this->makeGate(user: $user, subscription: $this->createStub(Subscription::class));
        self::assertTrue($gate->isGated());
    }

    public function testIsCompanyGatedFalseWhenAnyUserVerified(): void
    {
        $verified = (new User())->setVerified(true);
        $unverified = (new User())->setVerified(false);

        $company = new Company();
        $company->addUser($verified);
        $company->addUser($unverified);

        $gate = $this->makeGate(user: null, subscription: $this->createStub(Subscription::class));
        self::assertFalse($gate->isCompanyGated($company));
    }

    public function testIsCompanyGatedTrueWhenAllUsersUnverifiedAndSubscribed(): void
    {
        $unverified = (new User())->setVerified(false);

        $company = new Company();
        $company->addUser($unverified);

        $gate = $this->makeGate(user: null, subscription: $this->createStub(Subscription::class));
        self::assertTrue($gate->isCompanyGated($company));
    }

    public function testIsCompanyGatedFalseWithoutSubscription(): void
    {
        $unverified = (new User())->setVerified(false);

        $company = new Company();
        $company->addUser($unverified);

        $gate = $this->makeGate(user: null, subscription: null);
        self::assertFalse($gate->isCompanyGated($company));
    }

    public function testReasonFormatsLocalizedMessage(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('email_verification.gate.reason', ['%action%' => 'sending this invoice'])
            ->willReturn('Please verify your email address before sending this invoice.');

        $gate = $this->makeGate(translator: $translator);
        self::assertSame(
            'Please verify your email address before sending this invoice.',
            $gate->reason('sending this invoice'),
        );
    }

    public function testIsGatedMemoizedPerInstance(): void
    {
        $user = (new User())->setVerified(false);
        $companyId = new Ulid();
        $company = new Company();

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $companySelector = $this->createStub(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn($companyId);

        $companyRepo = $this->createMock(CompanyRepository::class);
        $companyRepo->expects(self::once())->method('find')->with($companyId)->willReturn($company);

        $subscriptionProvider = $this->createMock(SubscriptionProviderInterface::class);
        $subscriptionProvider->expects(self::once())
            ->method('getSubscriptionFor')
            ->with($company)
            ->willReturn($this->createStub(Subscription::class));

        $gate = new SaasEmailVerificationGate(
            $security,
            $companySelector,
            $companyRepo,
            $subscriptionProvider,
            $this->createStub(TranslatorInterface::class),
        );

        $gate->isGated();
        $gate->isGated();
        self::assertTrue(true);
    }

    public function testResetClearsCachesSoStaleStateIsNotReturned(): void
    {
        $user = (new User())->setVerified(false);
        $companyId = new Ulid();
        $company = new Company();

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $companySelector = $this->createStub(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn($companyId);

        $companyRepo = $this->createMock(CompanyRepository::class);
        $companyRepo->expects(self::exactly(2))->method('find')->with($companyId)->willReturn($company);

        $subscriptionProvider = $this->createMock(SubscriptionProviderInterface::class);
        $subscriptionProvider->expects(self::exactly(2))
            ->method('getSubscriptionFor')
            ->with($company)
            ->willReturn($this->createStub(Subscription::class));

        $gate = new SaasEmailVerificationGate(
            $security,
            $companySelector,
            $companyRepo,
            $subscriptionProvider,
            $this->createStub(TranslatorInterface::class),
        );

        self::assertTrue($gate->isGated());

        $gate->reset();

        // After reset, the next call must re-resolve dependencies (proving the cache was cleared).
        self::assertTrue($gate->isGated());
    }

    private function makeGate(
        ?User $user = null,
        ?Subscription $subscription = null,
        ?TranslatorInterface $translator = null,
    ): SaasEmailVerificationGate {
        $companyId = new Ulid();
        $company = new Company();

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $companySelector = $this->createStub(CompanySelectorInterface::class);
        $companySelector->method('getCompany')->willReturn($companyId);

        $companyRepo = $this->createStub(CompanyRepository::class);
        $companyRepo->method('find')->willReturn($company);

        $subscriptionProvider = $this->createStub(SubscriptionProviderInterface::class);
        $subscriptionProvider->method('getSubscriptionFor')->willReturn($subscription);

        return new SaasEmailVerificationGate(
            $security,
            $companySelector,
            $companyRepo,
            $subscriptionProvider,
            $translator ?? $this->createStub(TranslatorInterface::class),
        );
    }
}
