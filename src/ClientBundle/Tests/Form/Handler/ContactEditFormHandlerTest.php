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
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Form\Handler\ContactEditFormHandler;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\LazyManagerRegistry;
use Zenstruck\Foundry\Test\TestState;

class ContactEditFormHandlerTest extends FormHandlerTestCase
{
    use Factories;

    private string $firstName;

    private string $email;

    protected function setUp(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        TestState::bootFromContainer($kernel->getContainer());
        Factory::configuration()->setManagerRegistry(
            new LazyManagerRegistry(
                static function (): ChainManagerRegistry {
                    if (! static::$booted) {
                        static::bootKernel();
                    }

                    return TestState::initializeChainManagerRegistry(static::$kernel->getContainer());
                },
            ),
        );

        $kernel->shutdown();

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
        $company = CompanyFactory::new()->create()->object();
        $client = ClientFactory::createOne([
            'archived' => null,
            'company' => $company,
            'credit' => (new Credit())->setCompany($company)])
            ->object();

        $contact = ContactFactory::createOne([
            'firstName' => 'Test Name',
            'client' => $client,
            'company' => $company,
        ])->object();

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
