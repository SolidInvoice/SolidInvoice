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
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Handler\PasswordChangeHandler;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PasswordChangeHandlerTest extends FormHandlerTestCase
{
    private $userRepository;

    private $userPasswordHasher;

    private $tokenStorage;

    private $router;

    private $password;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = M::mock(UserRepository::class);
        $this->userPasswordHasher = new UserPasswordHasher(new PasswordHasherFactory([
            User::class => [
                'algorithm' => 'auto',
            ],
        ]));
        $this->tokenStorage = M::mock(TokenStorageInterface::class);
        $this->router = M::mock(RouterInterface::class);
        $this->password = $this->faker->password;

        $this->tokenStorage->shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn(new AnonymousToken($this->faker->sha1, 'anon.'));
    }

    public function getHandler()
    {
        return new PasswordChangeHandler($this->userRepository, $this->userPasswordHasher, $this->tokenStorage, $this->router);
    }

    protected function beforeSuccess(FormRequest $form, $data): void
    {
        $this->userPasswordHasher->shouldReceive('encodePassword')
            ->once()
            ->with($data, $this->password)
            ->andReturn(password_hash($this->password, PASSWORD_DEFAULT));
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('profile', $response->getTargetUrl());
        self::assertTrue(password_verify($this->password, $data->getPassword()));
        self::assertSame(FlashResponse::FLASH_SUCCESS, $response->getFlash()->key());
    }

    protected function getHandlerOptions(): array
    {
        return [
            'user' => new User(),
        ];
    }

    public function getFormData(): array
    {
        return [
          'plainPassword' => [
              'first' => $this->password,
              'second' => $this->password,
          ],
        ];
    }
}
