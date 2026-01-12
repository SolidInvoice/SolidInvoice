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

use Brick\Math\BigNumber;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\DTO\OnboardingData;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use function json_decode;
use function json_encode;

final class OnboardingManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyRepository $companyRepository,
        private readonly ClientRepository $clientRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly UserSettingRepository $userSettingRepository,
    ) {
    }

    /**
     * Check if user has completed onboarding
     */
    public function isOnboardingComplete(User $user): bool
    {
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardComplete,
        ]);

        return in_array($setting?->getValue(), ['true', 'dismissed'], true);
    }

    /**
     * Get current onboarding step
     */
    public function getCurrentStep(User $user): ?string
    {
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingStep,
        ]);

        return $setting?->getValue();
    }

    /**
     * Update onboarding step
     */
    public function setCurrentStep(User $user, string $step): void
    {
        $this->saveSetting($user, UserSettingType::OnboardingStep, $step);
        $this->entityManager->flush();
    }

    /**
     * Mark step as skipped
     * @throws JsonException
     */
    public function markStepSkipped(User $user, string $step): void
    {
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingSkipped,
        ]);

        $skipped = $setting ? json_decode($setting->getValue(), true, flags: JSON_THROW_ON_ERROR) : [];
        $skipped[] = $step;

        $this->saveSetting($user, UserSettingType::OnboardingSkipped, json_encode($skipped, JSON_THROW_ON_ERROR));
        $this->entityManager->flush();
    }

    /**
     * Complete onboarding process
     */
    public function completeOnboarding(User $user, OnboardingData $data): ?Invoice
    {
        // 1. Create company
        $company = $this->createCompany($data);
        $user->addCompany($company);
        $this->entityManager->persist($user);

        // 2. Create client (if not skipped)
        $client = null;
        if ($data->clientName && $data->clientEmail) {
            $client = $this->createClient($data, $company);
        } else {
            $this->markStepSkipped($user, 'client');
        }

        // 3. Create invoice (if not skipped and client exists)
        $invoice = null;
        if ($data->invoiceDescription && $data->invoiceAmount && $client instanceof Client) {
            $invoice = $this->createInvoice($data, $client, $company);
        } else {
            $this->markStepSkipped($user, 'invoice');
        }

        // 4. Mark onboarding complete
        $this->saveSetting($user, UserSettingType::OnboardComplete, 'true');
        $this->saveSetting(
            $user,
            UserSettingType::OnboardingCompletedAt,
            (new DateTimeImmutable())->format('Y-m-d H:i:s')
        );

        $this->entityManager->flush();

        return $invoice;
    }

    /**
     * Start onboarding (called after registration)
     */
    public function startOnboarding(User $user): void
    {
        $this->saveSetting(
            $user,
            UserSettingType::OnboardingStartedAt,
            (new DateTimeImmutable())->format('Y-m-d H:i:s')
        );
        $this->setCurrentStep($user, 'company');
    }

    /**
     * Dismiss onboarding (user chooses not to complete)
     */
    public function dismissOnboarding(User $user): void
    {
        $this->saveSetting($user, UserSettingType::OnboardComplete, 'dismissed');
        $this->saveSetting(
            $user,
            UserSettingType::OnboardingCompletedAt,
            (new DateTimeImmutable())->format('Y-m-d H:i:s')
        );
        $this->entityManager->flush();
    }

    /**
     * Helper: Create company from data
     */
    private function createCompany(OnboardingData $data): Company
    {
        $company = new Company();
        $company->setName($data->companyName);
        $company->currency = $data->companyCurrency ?? 'USD';

        $this->companyRepository->save($company);

        return $company;
    }

    /**
     * Helper: Create client from data
     */
    private function createClient(OnboardingData $data, Company $company): Client
    {
        $client = new Client();
        $client->setName($data->clientName);
        $client->setCompany($company);
        $client->setCurrencyCode($company->currency);

        // Create a contact with the email
        $contact = new Contact();
        $contact->setFirstName($data->clientName);
        $contact->setEmail($data->clientEmail);
        $contact->setClient($client);
        $client->addContact($contact);

        $this->clientRepository->save($client);

        return $client;
    }

    /**
     * Helper: Create invoice from data
     */
    private function createInvoice(OnboardingData $data, Client $client, Company $company): Invoice
    {
        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCompany($company);
        $invoice->setInvoiceId('1');
        $invoice->setStatus(Graph::STATUS_DRAFT);

        // Create a single line item
        $line = new Line();
        $line->setDescription($data->invoiceDescription);

        // Parse amount and create Money object
        $amount = BigNumber::of($data->invoiceAmount);
        $line->setPrice($amount);
        $line->setQty(1);

        $invoice->addLine($line);

        $this->invoiceRepository->save($invoice);

        return $invoice;
    }

    /**
     * Helper: Save user setting
     */
    private function saveSetting(User $user, UserSettingType $key, string $value): void
    {
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => $key,
        ]);

        if (! $setting instanceof UserSetting) {
            $setting = new UserSetting();
            $setting->setUser($user);
            $setting->setKey($key);
        }

        $setting->setValue($value);
        $this->entityManager->persist($setting);
    }
}
