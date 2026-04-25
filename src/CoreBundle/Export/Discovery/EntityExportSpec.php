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

namespace SolidInvoice\CoreBundle\Export\Discovery;

/**
 * Describes an entity class that should be included in a full company export.
 */
final readonly class EntityExportSpec
{
    /**
     * @param class-string $entityClass
     * @param list<string> $includedProperties Property names to emit for this entity.
     */
    public function __construct(
        public string $entityClass,
        public string $filename,
        public array $includedProperties,
    ) {
    }
}
