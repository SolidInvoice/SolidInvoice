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

use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Form\Handler\ContactAddFormHandler;
use CSBill\CoreBundle\Templating\Template;
use CSBill\FormBundle\Test\FormHandlerTestCase;
use JMS\Serializer\SerializerBuilder;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactAddFormHandlerTest extends FormHandlerTestCase
{
    public function getHandler()
    {
        $handler = new ContactAddFormHandler();
        $handler->setDoctrine($this->registry);
        $handler->setSerializer(SerializerBuilder::create()->build());

        return $handler;
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

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertInstanceOf(Contact::class, $data);
        $this->assertCount(1, $this->em->getRepository('CSBillClientBundle:Contact')->findAll());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
        $this->assertSame($this->getHandler()->getTemplate(), $formRequest->getResponse()->getTemplate());
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
            'CSBillClientBundle:Contact',
        ];
    }
}
