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

namespace SolidInvoice\CoreBundle\Tests\Migration;

/**
 * Connection details for the real database server
 * {@see MigrationIsIdempotentAcrossLineagesTestCase} verifies against, resolved from the
 * ambient SOLIDINVOICE_DATABASE_URL rather than a hardcoded list.
 */
final readonly class Engine
{
    public function __construct(
        public string $driver,
        public string $host,
        public int $port,
        public string $user,
        public string $password,
        public string $serverVersion,
    ) {
    }
}
