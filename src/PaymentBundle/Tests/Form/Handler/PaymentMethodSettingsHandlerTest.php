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

namespace SolidInvoice\PaymentBundle\Tests\Form\Handler;

use Mockery as M;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Form\Handler\PaymentMethodSettingsHandler;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PaymentMethodSettingsHandlerTest extends FormHandlerTestCase
{
    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->once()
            ->with('_xhr_payments_settings', ['method' => 'test_method'])
            ->andReturn('/payment/test_method');

        $handler = new PaymentMethodSettingsHandler(new PaymentFactories(), $router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        $method = new PaymentMethod();
        $method->setName('Test')
            ->setGatewayName('test_method')
            ->setFactoryName('offline');

        return [
            'payment_method' => $method,
        ];
    }

    public function getFormData(): array
    {
        return [
            'payment_methods' => [
                'name' => 'My Test Payment',
                'enabled' => true,
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        /* @var PaymentMethod $data */
        self::assertSame('My Test Payment', $data->getName());
        self::assertTrue($data->isEnabled());
        self::assertCount(4, $this->em->getRepository(PaymentMethod::class)->findAll());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
    }
}
