<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Tests\DependencyInjection;

use SolidInvoice\ApiBundle\DependencyInjection\SolidInvoiceApiExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class SolidInvoiceApiExtensionTest extends AbstractExtensionTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions()
    {
        return [
            new SolidInvoiceApiExtension(),
        ];
    }

    /**
     * @test
     */
    public function testLoad()
    {
        $this->load();

        $this->assertContainerBuilderHasService('api_token_authenticator', 'SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api_token_authenticator', 0, 'api_token_user_provider');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api_token_authenticator', 1, 'doctrine');

        $this->assertContainerBuilderHasService('api_token_user_provider', 'SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api_token_user_provider', 0, 'doctrine');

        $this->assertContainerBuilderHasService('api.success', 'SolidInvoice\ApiBundle\Event\Listener\AuthenticationSuccessHandler');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api.success', 0, 'api.token.manager');

        $this->assertContainerBuilderHasService('api.failure', 'SolidInvoice\ApiBundle\Event\Listener\AuthenticationFailHandler');

        $this->assertContainerBuilderHasService('api.token.manager', 'SolidInvoice\ApiBundle\ApiTokenManager');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api.token.manager', 0, 'doctrine');
    }
}
