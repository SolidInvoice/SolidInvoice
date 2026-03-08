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

namespace SolidInvoice\CoreBundle\Config\Loader;

use SolidInvoice\CoreBundle\ConfigWriter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use Symfony\Component\Uid\Uuid;

final class BuildIdLoader implements EnvVarLoaderInterface
{
    public function __construct(
        private readonly ConfigWriter $configWriter,
        #[Autowire(env: 'default::SOLIDINVOICE_BUILD_ID')]
        private readonly ?string $buildId,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function loadEnvVars(): array
    {
        if ($this->buildId !== null && $this->buildId !== '') {
            return [];
        }

        try {
            $buildId = (string) Uuid::v7();
            $this->configWriter->save(['BUILD_ID' => $buildId]);

            return ['SOLIDINVOICE_BUILD_ID' => $buildId];
        } catch (\Exception) {
            // Vault not yet initialized (app not installed) — skip silently
            return [];
        }
    }
}
