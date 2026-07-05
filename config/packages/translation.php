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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

// Locales SolidInvoice can be translated into. English is the source/reference locale;
// everything else falls back to it. This single list is what the translation provider
// pushes/pulls, so adding a language is a one-line change here.
$locales = [
    'en',
    'af', 'ar', 'bg', 'ca', 'cs', 'da', 'de', 'el', 'es', 'fa', 'fi', 'fr',
    'he', 'hu', 'id', 'it', 'ja', 'ko', 'nb', 'nl', 'pl', 'pt', 'pt_BR',
    'ro', 'ru', 'sk', 'sr', 'sv', 'tr', 'uk', 'vi', 'zh_CN', 'zh_TW',
];

return App::config([
    'framework' => [
        'default_locale' => env('SOLIDINVOICE_LOCALE'),
        'translator' => [
            'fallbacks' => ['en'],
            // Provider-agnostic push/pull integration. The DSN selects the provider
            // (CrowdIn by default, but any Symfony translation provider works), and is
            // empty by default so self-hosted installs are unaffected — the provider is
            // only contacted by the `translation:push` / `translation:pull` commands.
            'providers' => [
                'crowdin' => [
                    'dsn' => env('SOLIDINVOICE_TRANSLATION_DSN'),
                    'domains' => ['messages', 'email', 'validators'],
                    'locales' => $locales,
                ],
            ],
        ],
    ],
]);
