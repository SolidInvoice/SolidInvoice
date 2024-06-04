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
use Mockery\MockInterface;
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
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class PasswordChangeHandlerTest extends FormHandlerTestCase
{
    /**
     * @var UserRepository&M\MockInterface
     */
    private MockInterface&UserRepository $userRepository;

    private UserPasswordHasher $userPasswordHasher;

    /**
     * @var TokenStorageInterface&M\MockInterface
     */
    private MockInterface&TokenStorageInterface $tokenStorage;

    /**
     * @var RouterInterface&M\MockInterface
     */
    private MockInterface&RouterInterface $router;

    private string $password;

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
            ->andReturn(new NullToken());
    }

    public function getHandler(): PasswordChangeHandler
    {
        return new PasswordChangeHandler($this->userRepository, $this->userPasswordHasher, $this->tokenStorage, $this->router);
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('profile', $response->getTargetUrl());
        self::assertTrue(password_verify($this->password, (string) $data->getPassword()));
        self::assertSame(FlashResponse::FLASH_SUCCESS, $response->getFlash()->key());
    }

    /**
     * @return array{user: User}
     */
    protected function getHandlerOptions(): array
    {
        return [
            'user' => new User(),
        ];
    }

    /**
     * @return array{plainPassword: array{first: string, second: string}}
     */
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
