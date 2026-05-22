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

namespace SolidInvoice\CoreBundle\Company;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Config\LoadsBundleViewTemplate;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use function dirname;

/**
 * Backfills the seeded "Default" billing templates on first login for a company.
 *
 * The same six rows are seeded by {@see DefaultData} on company creation,
 * so for freshly installed sites this initializer is a no-op. It exists to
 * cover the upgrade path: companies created before the billing_templates
 * table existed need their default rows seeded the first time they access
 * the billing-template UI (or the resolver).
 *
 * @see \SolidInvoice\CoreBundle\Tests\Company\BillingTemplateInitializerTest
 */
final class BillingTemplateInitializer
{
    use LoadsBundleViewTemplate;

    /**
     * @var array<string, array{dir: string, variants: array<string, string>}>
     */
    private const BILLING_TEMPLATE_SOURCES = [
        BillingTemplate::TYPE_INVOICE => [
            'dir' => __DIR__ . '/../../InvoiceBundle/Resources/views',
            'variants' => [
                BillingTemplate::VARIANT_HTML => 'invoice_template.html.twig',
                BillingTemplate::VARIANT_PDF => 'Pdf/invoice.html.twig',
                BillingTemplate::VARIANT_EMAIL => 'Email/invoice.html.twig',
            ],
        ],
        BillingTemplate::TYPE_QUOTE => [
            'dir' => __DIR__ . '/../../QuoteBundle/Resources/views',
            'variants' => [
                BillingTemplate::VARIANT_HTML => 'quote_template.html.twig',
                BillingTemplate::VARIANT_PDF => 'Pdf/quote.html.twig',
                BillingTemplate::VARIANT_EMAIL => 'Email/quote.html.twig',
            ],
        ],
    ];

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly BillingTemplateRepository $repository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function ensureDefaults(): void
    {
        $companyId = $this->companySelector->getCompany();

        if (null === $companyId) {
            return;
        }

        $entityManager = $this->registry->getManager();
        $company = $entityManager->getRepository(Company::class)->find($companyId);

        if (! $company instanceof Company) {
            return;
        }

        $created = false;

        foreach (self::BILLING_TEMPLATE_SOURCES as $type => $config) {
            $bundleViewsDir = $config['dir'];

            foreach ($config['variants'] as $variant => $relativePath) {
                $existing = $this->repository->findOneBy([
                    'type' => $type,
                    'variant' => $variant,
                    'system' => true,
                ]);

                if (null !== $existing) {
                    continue;
                }

                $template = new BillingTemplate();
                $template->setType($type);
                $template->setVariant($variant);
                $template->setName(BillingTemplate::DEFAULT_NAME);
                $template->setContent($this->loadBundleViewTemplate($bundleViewsDir, $relativePath));
                $template->setSystem(true);
                $template->setActive(
                    null === $this->repository->findActive($type, $variant)
                );
                $template->setCompany($company);

                $entityManager->persist($template);
                $created = true;
            }
        }

        if ($created) {
            $entityManager->flush();
        }
    }
}
