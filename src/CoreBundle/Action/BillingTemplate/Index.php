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

namespace SolidInvoice\CoreBundle\Action\BillingTemplate;

use SolidInvoice\CoreBundle\Company\BillingTemplateInitializer;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use Symfony\Bridge\Twig\Attribute\Template;

final readonly class Index
{
    public function __construct(
        private BillingTemplateRepository $repository,
        private BillingTemplateInitializer $initializer,
    ) {
    }

    /**
     * @return array{grouped: array<string, array<string, list<BillingTemplate>>>}
     */
    #[Template('@SolidInvoiceCore/BillingTemplate/index.html.twig')]
    public function __invoke(): array
    {
        // Backfill any missing system templates for the current company.
        $this->initializer->ensureDefaults();

        $grouped = [];

        foreach ([BillingTemplate::TYPE_INVOICE, BillingTemplate::TYPE_QUOTE] as $type) {
            foreach ([BillingTemplate::VARIANT_HTML, BillingTemplate::VARIANT_PDF, BillingTemplate::VARIANT_EMAIL] as $variant) {
                $grouped[$type][$variant] = $this->repository->findAllForVariant($type, $variant);
            }
        }

        return ['grouped' => $grouped];
    }
}
