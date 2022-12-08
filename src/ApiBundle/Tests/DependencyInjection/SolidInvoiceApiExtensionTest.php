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

namespace SolidInvoice\ApiBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\DependencyInjection\SolidInvoiceApiExtension;
use SolidInvoice\ApiBundle\Event\Listener\AuthenticationFailHandler;
use SolidInvoice\ApiBundle\Event\Listener\AuthenticationSuccessHandler;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;

class SolidInvoiceApiExtensionTest extends AbstractExtensionTestCase
{
    use MockeryPHPUnitIntegration;

    protected function getContainerExtensions(): array
    {
        return [
            new SolidInvoiceApiExtension(),
        ];
    }

    public function testLoad(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(ApiTokenAuthenticator::class);
        $this->assertContainerBuilderHasService(ApiTokenUserProvider::class);
        $this->assertContainerBuilderHasService(AuthenticationSuccessHandler::class);
        $this->assertContainerBuilderHasService(AuthenticationFailHandler::class);
        $this->assertContainerBuilderHasService(ApiTokenManager::class);
    }
}
