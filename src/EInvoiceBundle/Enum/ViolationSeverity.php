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

namespace SolidInvoice\EInvoiceBundle\Enum;

use UnhandledMatchError;

enum ViolationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';

    public static function fromSchematronFlag(string $flag): self
    {
        // The default arm re-throws the same error PHP already throws for an
        // unmatched case. It exists to satisfy PHPStan, which cannot prove
        // exhaustiveness over the open string domain. It must never map an
        // unknown flag to a severity.
        return match ($flag) {
            'fatal' => self::Error,
            'warning' => self::Warning,
            default => throw new UnhandledMatchError($flag),
        };
    }
}
