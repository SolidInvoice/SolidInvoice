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

namespace SolidInvoice;

use const PHP_VERSION;
use const PHP_VERSION_ID;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Requirements\SymfonyRequirements;
use function get_cfg_var;
use function getcwd;
use function htmlspecialchars;
use function is_writable;
use function str_replace;

/**
 * @codeCoverageIgnore
 */
class AppRequirements extends SymfonyRequirements
{
    public function __construct(
        #[Autowire(env: 'SOLIDINVOICE_CONFIG_DIR')]
        string $configDir,
        #[Autowire(param: 'kernel.cache_dir')]
        string $cacheDir,
        #[Autowire(param: 'kernel.logs_dir')]
        string $logsDir,
        TranslatorInterface $translator,
    ) {
        $this->addRequirement(
            PHP_VERSION_ID >= 80400,
            $translator->trans('installation.requirements_check.php_version.test', ['%required%' => '8.4.0', '%installed%' => PHP_VERSION]),
            $translator->trans('installation.requirements_check.php_version.help', ['%required%' => '8.4.0', '%installed%' => PHP_VERSION]),
        );

        $configDir = dirname($configDir);
        foreach ([$configDir, $cacheDir, $logsDir] as $dir) {
            $trimmedDir = str_replace(dirname(getcwd()), '.', $dir);

            $this->addRequirement(
                is_writable($dir),
                $translator->trans('installation.requirements_check.writable_dir.test', ['%dir%' => $trimmedDir]),
                $translator->trans('installation.requirements_check.writable_dir.help', ['%dir%' => htmlspecialchars($trimmedDir)]),
            );
        }

        parent::__construct();

        $this->addRequirement(
            extension_loaded('openssl'),
            $translator->trans('installation.requirements_check.openssl.test'),
            $translator->trans('installation.requirements_check.openssl.help'),
        );

        $this->addRecommendation(
            extension_loaded('mbstring'),
            $translator->trans('installation.requirements_check.mbstring.test'),
            $translator->trans('installation.requirements_check.mbstring.help'),
        );

        $this->addRecommendation(
            extension_loaded('gd'),
            $translator->trans('installation.requirements_check.gd.test'),
            $translator->trans('installation.requirements_check.gd.help'),
        );
    }

    /**
     * @return false|array<string, mixed>|string
     */
    public function getPhpIniPath(): false | array | string
    {
        return get_cfg_var('cfg_file_path');
    }

    #[Override]
    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null): void
    {
        if ('PDO should be installed' === $testMessage || preg_match('#PDO should have some drivers installed#', $testMessage)) {
            $this->addRequirement($fulfilled, $testMessage, $helpHtml, $helpText);
            return;
        }

        parent::addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText);
    }
}
