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

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Handler\ContactAddFormHandler;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Test\Traits\SymfonyKernelTrait;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactAddFormHandlerTest extends FormHandlerTestCase
{
    use SymfonyKernelTrait;

    public function getHandler(): ContactAddFormHandler
    {
        $handler = new ContactAddFormHandler();
        $handler->setDoctrine($this->registry);
        $handler->setSerializer(static::getContainer()->get('serializer'));

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        return ['contact' => (new Contact())->setClient(ClientFactory::new()->create()->object())];
    }

    public function getFormData(): array
    {
        return [
            'contact' => [
                'firstName' => $this->faker->firstName,
                'email' => $this->faker->email,
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertInstanceOf(Contact::class, $data);
        self::assertCount(1, $this->em->getRepository(Contact::class)->findAll());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
        self::assertSame($this->getHandler()->getTemplate(), $formRequest->getResponse()->getTemplate());
    }
}
