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

namespace SolidInvoice\UserBundle\Tests\Form\Handler;

use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Form\Handler\ApiFormHandler;
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

    protected function getHandlerOptions(): array
    {
        return ['api_token' => new ApiToken()];
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertCount(1, $this->em->getRepository(ApiToken::class)->findAll());
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('test_token', $data->getName());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    public function getFormData(): array
    {
        return [
            'api_token' => [
                'name' => 'test_token',
            ],
        ];
    }
}
