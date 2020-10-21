<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PasswordChangeHandlerTest extends FormHandlerTestCase
{
    private $userRepository;
    private $userPasswordEncoder;
    private $tokenStorage;
    private $router;
    private $password;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = M::mock(UserRepository::class);
        $this->userPasswordEncoder = M::mock(UserPasswordEncoderInterface::class);
        $this->tokenStorage = M::mock(TokenStorageInterface::class);
        $this->router = M::mock(RouterInterface::class);
        $this->password = $this->faker->password;

        $this->tokenStorage->shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn(new AnonymousToken($this->faker->sha1, 'anon.'));
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler()
    {
        return new PasswordChangeHandler($this->userRepository, $this->userPasswordEncoder, $this->tokenStorage, $this->router);
    }

    protected function beforeSuccess(FormRequest $form, $data): void
    {
        $this->userPasswordEncoder->shouldReceive('encodePassword')
            ->once()
            ->with($data, $this->password)
            ->andReturn(password_hash($this->password, PASSWORD_DEFAULT));
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertInstanceOf(FlashResponse::class, $response);
        static::assertSame('profile', $response->getTargetUrl());
        static::assertTrue(password_verify($this->password, $data->getPassword()));
        static::assertSame(FlashResponse::FLASH_SUCCESS, $response->getFlash()->key());
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
