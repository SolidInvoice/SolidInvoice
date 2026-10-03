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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine;

/**
 * The severity a ConformanceEngine reports a violation at. RuleCase asserts on this directly:
 * an <error> rule must fire at Fatal, a <warning> rule must fire at Warning.
 */
enum Severity: string
{
    case Fatal = 'fatal';

    case Warning = 'warning';

    case Information = 'information';
}
