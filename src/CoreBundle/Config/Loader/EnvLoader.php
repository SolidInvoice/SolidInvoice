<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2020
 */

namespace SolidInvoice\CoreBundle\Config\Loader;

use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;

final class EnvLoader implements EnvVarLoaderInterface
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function loadEnvVars(): array
    {
        $envFile = $this->projectDir.'/app/config/env.php';

        if (!\file_exists($envFile)) {
            return [];
        }

        return require $envFile;
    }
}
