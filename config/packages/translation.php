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

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $config): void {
    $config
        ->defaultLocale(env('SOLIDINVOICE_LOCALE'))
        ->translator()
        ->fallbacks(['en'])
    ;

    $config->translator()
        ->provider('crowdin')
        ->dsn(env('CROWDIN_DSN')->default(''))
        ->locales([
            'en', // English
            'af', // Afrikaans
            'ar', // Arabic
            'ca', // Catalan
            'zh-CN', // Chinese Simplified
            'zh-TW', // Chinese Traditional
            'cs', // Czech
            'da', // Danish
            'nl', // Dutch
            'fi', // Finnish
            'fr', // French
            'de', // German
            'el', // Greek
            'he', // Hebrew
            'hu', // Hungarian
            'it', // Italian
            'ja', // Japanese
            'ko', // Korean
            'no', // Norwegian
            'pl', // Polish
            'pt-PT', // Portuguese
            'pt-BR', // Portuguese, Brazilian
            'ro', // Romanian
            'ru', // Russian
            'sr', // Serbian (Cyrillic)
            'es-ES', // Spanish
            'sv-SE', // Swedish
            'tr', // Turkish
            'uk', // Ukrainian
            'vi', // Vietnamese
        ])
    ;
};
