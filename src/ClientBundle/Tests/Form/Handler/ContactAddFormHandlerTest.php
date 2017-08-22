<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Tests\Form\Handler;

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Handler\ContactAddFormHandler;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Test\Traits\SymfonyKernelTrait;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactAddFormHandlerTest extends FormHandlerTestCase
{
    use SymfonyKernelTrait;

    public function getHandler()
    {
        $handler = new ContactAddFormHandler();
        $handler->setDoctrine($this->registry);
        $handler->setSerializer($this->container->get('serializer'));

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        return ['contact' => new Contact()];
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

    protected function assertOnSuccess(? Response $response, $data, FormRequest $form)
    {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertInstanceOf(Contact::class, $data);
        $this->assertCount(1, $this->em->getRepository('SolidInvoiceClientBundle:Contact')->findAll());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
        $this->assertSame($this->getHandler()->getTemplate(), $formRequest->getResponse()->getTemplate());
    }

    protected function getEntityNamespaces(): array
    {
        return [
            'SolidInvoiceClientBundle' => 'SolidInvoice\ClientBundle\Entity',
        ];
    }

    protected function getEntities(): array
    {
        return [
            'SolidInvoiceClientBundle:Contact',
        ];
    }
}
