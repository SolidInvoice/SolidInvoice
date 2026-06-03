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

namespace SolidInvoice\CoreBundle\Telemetry\Command;

use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'solidinvoice:telemetry:ping',
    description: 'Send the daily telemetry heartbeat to SolidWorx Insights',
)]
#[AsCronTask('#daily', schedule: 'telemetry_ping')]
final class SendTelemetryPingCommand extends Command
{
    public function __construct(
        private readonly Telemetry $telemetry,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $this->telemetry->ping();

        return self::SUCCESS;
    }
}
