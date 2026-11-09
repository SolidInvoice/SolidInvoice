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

use Google\Client;
use SolidInvoice\SaasBundle\Security\OneTap\GoogleIdTokenVerifier;
use SolidInvoice\SaasBundle\Security\OneTap\IdTokenVerifierInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // The Google API client used to verify One Tap ID tokens. The client id is
    // the expected audience of the token; the cache pool stores Google's signing
    // certificates so they are not re-fetched on every verification.
    $services->set(Client::class)->arg('$config', ['client_id' => '%env(SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_ID)%'])
        ->call('setCache', [service('cache.app')]);

    $services->alias(IdTokenVerifierInterface::class, GoogleIdTokenVerifier::class);
};
