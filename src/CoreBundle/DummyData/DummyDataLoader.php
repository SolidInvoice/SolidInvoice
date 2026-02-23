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

namespace SolidInvoice\CoreBundle\DummyData;

use SolidInvoice\CoreBundle\Entity\Company;

final class DummyDataLoader
{
    /**
     * @param iterable<DummyDataLoaderInterface> $loaders
     */
    public function __construct(
        private readonly iterable $loaders
    ) {
    }

    public function load(Company $company): void
    {
        foreach ($this->loaders as $loader) {
            $loader->load($company);
        }
    }
}
