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
use SolidInvoice\ClientBundle\Form\Handler\ContactEditFormHandler;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactEditFormHandlerTest extends FormHandlerTestCase
{
    private string $firstName;

    private string $email;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstName = $this->faker->firstName;
        $this->email = $this->faker->email;
    }

    public function getHandler(): ContactEditFormHandler
    {
        $handler = new ContactEditFormHandler();
        $handler->setDoctrine($this->registry);
        $handler->setSerializer(static::getContainer()->get('serializer'));

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        $contact = new Contact();
        $contact->setFirstName('Test Name')
            ->setEmail('test@test.com')
            ->setClient(ClientFactory::new()->create()->object());
        $this->em->persist($contact);
        $this->em->flush();

        return [
            'contact' => $contact,
        ];
    }

    public function getFormData(): array
    {
        return [
            'contact' => [
                'firstName' => $this->firstName,
                'email' => $this->email,
            ],
        ];
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertInstanceOf(Contact::class, $data);
        self::assertCount(1, $this->em->getRepository(Contact::class)->findAll());
        self::assertSame($this->firstName, $data->getFirstName());
        self::assertSame($this->email, $data->getEmail());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
        self::assertSame($this->getHandler()->getTemplate(), $formRequest->getResponse()->getTemplate());
    }
}
