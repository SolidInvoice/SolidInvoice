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

namespace SolidInvoice\TaxBundle\Tests\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Form\Handler\TaxFormHandler;
use Mockery as M;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class TaxFormHandlerTest extends FormHandlerTestCase
{
    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->with('_tax_rates')
            ->andReturn('/tax/rates');

        $handler = new TaxFormHandler($router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    /**
     * @return array
     */
    public function getFormData(): array
    {
        return [
            'tax' => [
                'name' => 'VAT',
                'rate' => 14,
                'type' => 'Inclusive',
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form): void
    {
        $this->assertCount(1, $this->em->getRepository(Tax::class)->findAll());
        $tax = $this->em->getRepository(Tax::class)->findAll()[0];
        $this->assertSame('VAT', $tax->getName());
        $this->assertSame(14.0, $tax->getRate());
        $this->assertSame('Inclusive', $tax->getType());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(FlashResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }
}
