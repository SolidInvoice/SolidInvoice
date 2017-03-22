<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Tests\DependencyInjection;

use CSBill\ApiBundle\DependencyInjection\CSBillApiExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class CSBillApiExtensionTest extends AbstractExtensionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensions()
    {
        return [
            new CSBillApiExtension(),
        ];
    }

    /**
     * @test
     */
    public function testLoad()
    {
        $this->load();

        $this->assertContainerBuilderHasService('api_token_authenticator', 'CSBill\ApiBundle\Security\ApiTokenAuthenticator');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api_token_authenticator', 0, 'api_token_user_provider');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api_token_authenticator', 1, 'doctrine');

        $this->assertContainerBuilderHasService('api_token_user_provider', 'CSBill\ApiBundle\Security\Provider\ApiTokenUserProvider');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api_token_user_provider', 0, 'doctrine');

        $this->assertContainerBuilderHasService('api.success', 'CSBill\ApiBundle\Event\Listener\AuthenticationSuccessHandler');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api.success', 0, 'api.token.manager');

        $this->assertContainerBuilderHasService('api.failure', 'CSBill\ApiBundle\Event\Listener\AuthenticationFailHandler');

        $this->assertContainerBuilderHasService('api.token.manager', 'CSBill\ApiBundle\ApiTokenManager');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('api.token.manager', 0, 'doctrine');
    }
}
