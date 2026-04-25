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

namespace SolidInvoice\CoreBundle\Export;

use DateTimeInterface;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;

final class ManifestGenerator
{
    /**
     * @param array<string, int> $entityCounts
     * @return array<string, mixed>
     */
    public function generate(ExportJob $job, array $entityCounts): array
    {
        return [
            'solidinvoice_version' => SolidInvoiceCoreBundle::VERSION,
            'export_id' => $job->getId()->toBase58(),
            'company_id' => $job->getCompany()->getId()->toBase58(),
            'requested_by' => $job->getRequestedBy()->toBase58(),
            'requested_at' => $job->getCreatedAt()->format(DateTimeInterface::ATOM),
            'format' => $job->getFormat()->value,
            'entity_counts' => $entityCounts,
        ];
    }
}
