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
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\LazyManagerRegistry;
use Zenstruck\Foundry\Test\TestState;

class ContactAddFormHandlerTest extends FormHandlerTestCase
{
    use Factories;

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

    public function getHandler(): ContactAddFormHandler
    {
        $handler = new ContactAddFormHandler();
        $handler->setDoctrine($this->registry);
        $handler->setSerializer(static::getContainer()->get('serializer'));

        return $handler;
    }

    /**
     * @return array{contact: Contact}
     */
    protected function getHandlerOptions(): array
    {
        $client = ClientFactory::createOne()->object();

        return ['contact' => (new Contact())->setClient($client)];
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
