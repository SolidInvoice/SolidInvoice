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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Case;

/**
 * A single row a conformance data provider hands to a test method: either a real case
 * (RuleCase or InstanceCase) or the sentinel that stands in for an unfetched corpus.
 */
interface ConformanceCase
{
    /**
     * Stable, greppable, and what --testdox prints.
     */
    public function id(): string;
}
