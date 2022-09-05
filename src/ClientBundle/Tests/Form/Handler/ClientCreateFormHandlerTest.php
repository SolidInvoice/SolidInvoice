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

namespace SolidInvoice\ClientBundle\Tests\Form\Handler;

use Mockery as M;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Handler\ClientCreateFormHandler;
use SolidInvoice\ClientBundle\Model\Status;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ClientCreateFormHandlerTest extends FormHandlerTestCase
{
    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $this->registry->getRepository(Client::class);
        $router = M::mock(RouterInterface::class);

        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/client/1');

        $handler = new ClientCreateFormHandler($router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    public function getFormData(): array
    {
        return [
            'client' => [
                'name' => $this->faker->company,
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $client): void
    {
        /** @var Client $client */

        self::assertSame(Status::STATUS_ACTIVE, $client->getStatus());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertCount(1, $response->getFlash());
        self::assertCount(1, $this->em->getRepository(Client::class)->findAll());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
    }
}
