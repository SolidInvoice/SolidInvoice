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

namespace CSBill\UserBundle\Tests\Form\Handler;

use CSBill\ApiBundle\ApiTokenManager;
use CSBill\CoreBundle\Templating\Template;
use CSBill\FormBundle\Test\FormHandlerTestCase;
use CSBill\UserBundle\Form\Handler\ApiFormHandler;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiFormHandlerTest extends FormHandlerTestCase
{
    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $handler = new ApiFormHandler(new ApiTokenManager($this->registry));
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertCount(1, $this->em->getRepository('CSBillUserBundle:ApiToken')->findAll());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('test_token', $data->getName());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    /**
     * @return array
     */
    public function getFormData(): array
    {
        return [
            'api_token' => [
                'name' => 'test_token',
            ],
        ];
    }

    protected function getEntities(): array
    {
        return [
            'CSBillUserBundle:ApiToken',
            'CSBillUserBundle:User',
        ];
    }

    protected function getEntityNamespaces(): array
    {
        return [
            'CSBillUserBundle' => 'CSBill\UserBundle\Entity',
        ];
    }
}
