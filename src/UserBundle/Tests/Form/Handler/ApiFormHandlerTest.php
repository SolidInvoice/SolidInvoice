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
    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->em->getRepository(ApiToken::class)->findAll() as $user) {
            $this->em->remove($user);
        }
    }

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

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertCount(1, $this->em->getRepository(ApiToken::class)->findAll());
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame('test_token', $data->getName());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
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
