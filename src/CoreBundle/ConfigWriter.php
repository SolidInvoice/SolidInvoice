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

namespace SolidInvoice\CoreBundle;

use const DIRECTORY_SEPARATOR;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function basename;
use function function_exists;
use function opcache_is_script_cached;
use function rtrim;
use function str_starts_with;
use function strtoupper;

readonly class ConfigWriter
{
    private const CONFIG_PREFIX = 'SOLIDINVOICE_';

    private string $pathPrefix;

    public function __construct(
        private AbstractVault $vault,
        #[Autowire(env: 'SOLIDINVOICE_CONFIG_DIR')]
        string $secretsDir,
    ) {
        $this->pathPrefix = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $secretsDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($secretsDir) . '.';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function save(array $config): void
    {
        $this->vault->generateKeys();

        $opCacheEnabled = function_exists('opcache_invalidate');

        foreach ($config as $key => $value) {
            if (! str_starts_with($key, self::CONFIG_PREFIX)) {
                $key = self::CONFIG_PREFIX . $key;
            }

            $this->vault->seal(strtoupper($key), (string) $value);

            if ($opCacheEnabled && opcache_is_script_cached($this->pathPrefix . 'list.php')) {
                opcache_invalidate($this->pathPrefix . 'list.php', true);
            }
        }
    }
}
