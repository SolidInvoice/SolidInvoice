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

namespace SolidInvoice\CoreBundle\Test\Snapshot;

/**
 * Routes assertMatchesHtmlSnapshot() through StableHtmlDriver instead of the
 * host-libxml2-dependent driver spatie/phpunit-snapshot-assertions ships by
 * default. Use alongside Spatie\Snapshots\MatchesSnapshots (SOL-518/SOL-520).
 */
trait MatchesStableHtmlSnapshot
{
    public function assertMatchesHtmlSnapshot(string $actual, ?string $id = null): void
    {
        $this->assertMatchesSnapshot($actual, new StableHtmlDriver(), $id);
    }
}
