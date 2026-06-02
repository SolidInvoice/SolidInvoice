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

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies that the PDF base template (`_pdf_base.html.twig`) gates the
 * `system/general/hide_powered_by` setting on the `custom_branding` feature:
 *
 * - When the feature is disabled (Free/Solo plans), the "Powered By" line is
 *   always rendered regardless of the stored DB value.
 * - When the feature is enabled, the setting controls visibility as before.
 * - On self-hosted (NoopFeatureGate), `hide_powered_by=1` suppresses the line.
 *
 * The "Powered By" content lives in the `<pagefooter content-left=...>`
 * attribute of the rendered template (see `classic/pdf.html.twig` which
 * extends `_pdf_base.html.twig`).
 */
#[Group('functional')]
final class PdfBaseCustomBrandingGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string SETTING_KEY = 'system/general/hide_powered_by';

    public function testGatedPlanAlwaysShowsPoweredByEvenWhenSettingHides(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        // SaaS plan that does NOT include custom_branding: the gate is OFF.
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $key !== 'custom_branding');
        self::getContainer()->set(FeatureGate::class, $featureGate);

        $this->reloadCompany();
        // Persist hide_powered_by=1 in the database (the "I'm hiding it" intent
        // baked in from a prior plan that had custom_branding).
        $this->seedHidePoweredBy('1');

        $output = $this->renderPdfTemplate();

        // The "Powered By" attribute is populated despite the setting being '1'.
        self::assertStringContainsString('Powered By', $output);
    }

    public function testUngatedPlanRespectsHidePoweredBySetting(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);
        self::getContainer()->set(FeatureGate::class, $featureGate);

        $this->reloadCompany();
        $this->seedHidePoweredBy('1');

        $output = $this->renderPdfTemplate();

        // With custom_branding ON and hide_powered_by=1, the attribute is empty.
        self::assertStringNotContainsString('Powered By', $output);
    }

    public function testUngatedPlanShowsPoweredByWhenSettingNotHidden(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);
        self::getContainer()->set(FeatureGate::class, $featureGate);

        $this->reloadCompany();
        $this->seedHidePoweredBy('0');

        $output = $this->renderPdfTemplate();

        self::assertStringContainsString('Powered By', $output);
    }

    public function testSelfHostedNoopFeatureGateRespectsHidePoweredBySetting(): void
    {
        // Self-hosted FeatureGate (NoopFeatureGate) reports every feature as
        // enabled, so a stored hide_powered_by=1 takes effect.
        $this->seedHidePoweredBy('1');

        // Do NOT replace the gate — exercise the wired implementation.
        if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) === 'saas') {
            self::markTestSkipped('Self-hosted scenario is exercised in non-SaaS test runs only.');
        }

        $output = $this->renderPdfTemplate();

        self::assertStringNotContainsString('Powered By', $output);
    }

    private function reloadCompany(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $company = $em->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $this->company = $company;

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
    }

    private function seedHidePoweredBy(string $value): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        // Try to update via the repository first; if the row doesn't exist yet
        // (self-hosted has no SaasBundle ConfigProvider seeding it), persist a
        // new row directly so the template can read a deterministic value.
        $repo = $em->getRepository(Setting::class);
        $existing = $repo->findOneBy(['key' => self::SETTING_KEY]);

        if ($existing instanceof Setting) {
            $existing->setValue($value);
            $em->flush();

            return;
        }

        $setting = new Setting();
        $setting->setKey(self::SETTING_KEY);
        $setting->setValue($value);
        $setting->setType(CheckboxType::class);
        $setting->setDefaultValue('0');
        $setting->setCompany($this->company);

        $em->persist($setting);
        $em->flush();
    }

    private function renderPdfTemplate(): string
    {
        $invoice = $this->createFixtureInvoice();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->render(
            '@SolidInvoiceInvoice/Templates/classic/pdf.html.twig',
            ['invoice' => $invoice]
        );
    }

    private function createFixtureInvoice(): Invoice
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'name' => 'Acme Corp',
            'currencyCode' => 'USD',
        ]);

        $contact = ContactFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        return InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'invoiceId' => 'INV-FIXTURE-001',
            'due' => CarbonImmutable::now()->addDays(14),
            'paidDate' => null,
            'archived' => null,
            'terms' => 'Payment due within 30 days.',
            'notes' => 'Thank you for your business.',
            'balance' => BigInteger::of(150000),
            'total' => BigInteger::of(150000),
            'baseTotal' => BigInteger::of(150000),
            'tax' => BigInteger::of(0),
            'discount' => new Discount()->setType(null),
            'lines' => [
                new Line()
                    ->setDescription('Sample line item')
                    ->setPrice(BigInteger::of(75000))
                    ->setQty(2.0)
                    ->setTotal(BigInteger::of(150000)),
            ],
            'users' => [$contact],
        ])->_real();
    }
}
