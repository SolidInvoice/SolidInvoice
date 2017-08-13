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
use CSBill\ClientBundle\Form\Handler\ContactEditFormHandler;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Test\Traits\SymfonyKernelTrait;
use CSBill\FormBundle\Test\FormHandlerTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactEditFormHandlerTest extends FormHandlerTestCase
{
    use MockeryPHPUnitIntegration,
        SymfonyKernelTrait;

    private $firstName;

    private $email;

    protected function setUp()
    {
        parent::setUp();

        $this->firstName = $this->faker->firstName;
        $this->email = $this->faker->email;
    }

    public function getHandler()
    {
        $handler = new ContactEditFormHandler();
        $handler->setDoctrine($this->registry);
        $handler->setSerializer($this->container->get('serializer'));

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        $contact = new Contact();
        $contact->setFirstName('Test Name')
            ->setEmail('test@test.com');
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

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertInstanceOf(Contact::class, $data);
        $this->assertCount(1, $this->em->getRepository('CSBillClientBundle:Contact')->findAll());
        $this->assertSame($this->firstName, $data->getFirstName());
        $this->assertSame($this->email, $data->getEmail());
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
