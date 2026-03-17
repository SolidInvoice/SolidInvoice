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

namespace SolidInvoice\InstallBundle\Step;

use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\DTO\Installation;
use Symfony\Component\Uid\Uuid;

final class GenerateBuildIdStep implements InstallationStepInterface
{
    public function __construct(
        private readonly ConfigWriter $configWriter,
    ) {
    }

    public static function priority(): int
    {
        return 25;
    }

    public function execute(Installation $installationData, ?callable $callback = null): \Generator
    {
        $this->configWriter->save(['BUILD_ID' => (string) Uuid::v7()]);

        if ($callback !== null) {
            yield from $callback('Build ID generated');
        }
    }

    public static function getLabel(): string
    {
        return 'Generating build id';
    }
}
