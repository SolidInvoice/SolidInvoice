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

namespace SolidInvoice\TaxBundle\Tests\Form\Handler;

use Mockery as M;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Form\Handler\TaxFormHandler;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use function iterator_to_array;

class TaxFormHandlerTest extends FormHandlerTestCase
{
    public function getHandler(): FormHandlerInterface
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
     * @return array{tax: array{name: string, rate: int, type: string}}
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

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertCount(1, $this->em->getRepository(Tax::class)->findAll());
        $tax = $this->em->getRepository(Tax::class)->findAll()[0];
        self::assertSame('VAT', $tax->getName());
        self::assertSame(14.0, $tax->getRate());
        self::assertSame('Inclusive', $tax->getType());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertCount(1, iterator_to_array($response->getFlash()));
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
    }
}
