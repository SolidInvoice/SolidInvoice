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

namespace CSBill\ClientBundle\Tests\Form\Handler;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Form\Handler\ClientCreateFormHandler;
use CSBill\ClientBundle\Model\Status;
use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\FormBundle\Test\FormHandlerTestCase;
use Mockery as M;
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
        $router = M::mock(RouterInterface::class);

        $router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->with('_clients_view', ['id' => 1])
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

    protected function assertOnSuccess(?Response $response, $client, FormRequest $form)
    {
        /* @var Client $client */

        $this->assertSame(Status::STATUS_ACTIVE, $client->getStatus());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(FlashResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
        $this->assertCount(1, $this->em->getRepository('CSBillClientBundle:Client')->findAll());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    protected function getEntityNamespaces(): array
    {
        return [
            'CSBillClientBundle' => 'CSBill\ClientBundle\Entity',
        ];
    }

    protected function getEntities(): array
    {
        return [
            'CSBillClientBundle:Client',
            'CSBillClientBundle:Credit',
        ];
    }
}
