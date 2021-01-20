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
use SolidInvoice\ClientBundle\Form\Handler\ClientEditFormHandler;
use SolidInvoice\ClientBundle\Model\Status;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ClientEditFormHandlerTest extends FormHandlerTestCase
{
    private $clientName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientName = $this->faker->company;
    }

    public function getHandler()
    {
        $router = M::mock(RouterInterface::class);

        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn('/client/1');

        $handler = new ClientEditFormHandler($router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        $client = new Client();
        $client->setName('Test One')
            ->setStatus(Status::STATUS_ACTIVE);

        $this->em->persist($client);
        $this->em->flush();

        return [
            'client' => $client,
        ];
    }

    public function getFormData(): array
    {
        return [
            'client' => [
                'name' => $this->clientName,
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $client): void
    {
        /* @var Client $client */

        static::assertSame($this->clientName, $client->getName());
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertInstanceOf(FlashResponse::class, $response);
        static::assertCount(1, $response->getFlash());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        static::assertInstanceOf(Template::class, $formRequest->getResponse());
    }
}
