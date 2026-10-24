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
 * The document-level outcome expected of a whole-document conformance case. Fragment cases
 * (RuleCase) never produce a Verdict: a fragment is not schema-valid by construction.
 */
enum Verdict: string
{
    case Accept = 'accept';

    case Reject = 'reject';
}
