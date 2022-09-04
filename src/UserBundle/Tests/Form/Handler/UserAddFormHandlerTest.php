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

use Mockery as M;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Handler\UserAddFormHandler;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserAddFormHandlerTest extends FormHandlerTestCase
{
    private $router;

    private $userPasswordEncoder;

    private $password;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->password = $this->faker->password;
        $this->router = M::mock(RouterInterface::class);
        $this->userPasswordEncoder = M::mock(UserPasswordEncoderInterface::class);
    }

    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $handler = new UserAddFormHandler($this->userPasswordEncoder, $this->router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        return [
            'user' => $this->user,
        ];
    }

    protected function beforeSuccess(FormRequest $form, $data): void
    {
        $this->userPasswordEncoder->shouldReceive('encodePassword')
            ->once()
            ->with($data, $this->password)
            ->andReturn(password_hash($this->password, PASSWORD_DEFAULT));

        $this->router->shouldReceive('generate')
            ->once()
            ->with('_users_list')
            ->andReturn('/users');
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertCount(1, $this->em->getRepository(User::class)->findAll());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/users', $response->getTargetUrl());
        self::assertSame('test', $data->getUserName());
        self::assertTrue(password_verify($this->password, $data->getPassword()));
        self::assertCount(1, $response->getFlash());
        self::assertSame(FlashResponse::FLASH_SUCCESS, $response->getFlash()->key());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    public function getFormData(): array
    {
        return [
            'user' => [
                'username' => 'test',
                'email' => $this->faker->email,
                'plainPassword' => [
                    'first' => $this->password,
                    'second' => $this->password,
                ],
                'mobile' => $this->faker->phoneNumber,
                'enabled' => true,
            ],
        ];
    }
}
