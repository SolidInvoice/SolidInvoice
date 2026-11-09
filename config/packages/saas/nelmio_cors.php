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

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function trim;

// The Google One Tap widget lives on the marketing site (a separate origin) and
// calls these endpoints cross-origin. Only the explicitly allow-listed marketing
// origins may read the responses. The endpoints are stateless (no cookies), so
// credentials are intentionally not allowed.
//
// NelmioCorsBundle inspects `allow_origin` at container-build time (it scans for
// the "*" wildcard), so it cannot accept an env placeholder that resolves to an
// array at runtime. We therefore resolve the comma-separated allow-list here, at
// build time, into an array of exact origins. Changing the origins requires a
// cache rebuild — expected for a deploy-time setting — and gives exact-match
// semantics (stricter than a regex).
$rawOrigins = $_ENV['SOLIDINVOICE_ONE_TAP_ALLOWED_ORIGINS']
    ?? $_SERVER['SOLIDINVOICE_ONE_TAP_ALLOWED_ORIGINS']
    ?? '';

$allowedOrigins = array_values(array_filter(array_map(
    trim(...),
    explode(',', (string) $rawOrigins),
)));

return App::config([
    'nelmio_cors' => [
        'paths' => [
            '^/onetap/' => [
                'allow_origin' => $allowedOrigins,
                'allow_methods' => ['GET', 'POST', 'OPTIONS'],
                'allow_headers' => ['Content-Type'],
                'allow_credentials' => false,
                'max_age' => 3600,
            ],
        ],
    ],
]);
