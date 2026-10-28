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

namespace SolidInvoice\UserBundle\Onboarding\Manager;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\DTO\OnboardingData;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;

/**
 * @see \SolidInvoice\UserBundle\Tests\Onboarding\Manager\OnboardingManagerTest
 */
final readonly class OnboardingManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyRepository $companyRepository,
        private UserSettingRepository $userSettingRepository,
    ) {
    }

    public function isOnboardingComplete(User $user): bool
    {
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardComplete,
        ]);

        return in_array($setting?->getValue(), ['true', 'dismissed'], true);
    }

    public function completeOnboarding(User $user, OnboardingData $data): Company
    {
        $company = new Company();
        $company->setName($data->companyName);
        $company->currency = $data->companyCurrency ?? 'USD';
        $company->setCountry($data->companyCountry);
        $company->setOwner($user);

        $this->companyRepository->save($company);

        $user->addCompany($company);
        $this->entityManager->persist($user);

        $this->markComplete($user, 'true');

        return $company;
    }

    /**
     * Dismiss onboarding (user chooses not to complete)
     */
    public function dismissOnboarding(User $user): void
    {
        $this->markComplete($user, 'dismissed');
    }

    private function markComplete(User $user, string $value): void
    {
        $this->userSettingRepository->saveSetting($user, UserSettingType::OnboardComplete, $value);
        // AffiliateProgram times its prompt from this.
        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::OnboardingCompletedAt,
            CarbonImmutable::now()->format('Y-m-d H:i:s')
        );
        $this->entityManager->flush();
    }
}
