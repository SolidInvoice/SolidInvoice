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
use SolidInvoice\UserBundle\Form\Handler\ProfileEditFormHandler;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileEditHandlerTest extends FormHandlerTestCase
{
    private MockInterface&UserRepository $userRepository;

    private MockInterface&TokenStorageInterface $tokenStorage;

    private MockInterface&RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = M::mock(UserRepository::class);
        $this->tokenStorage = M::mock(TokenStorageInterface::class);
        $this->router = M::mock(RouterInterface::class);

        $this->tokenStorage
            ->shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn(new NullToken());
    }

    public function getHandler(): ProfileEditFormHandler
    {
        return new ProfileEditFormHandler($this->userRepository, $this->tokenStorage, $this->router);
    }

    protected function beforeSuccess(FormRequest $form, $data): void
    {
        $this->userRepository->shouldReceive('save')
            ->once()
            ->with($data);

        $this->router->shouldReceive('generate')
            ->once()
            ->with('_profile')
            ->andReturn('/profile');
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertSame('9876543210', $data->getMobile());
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/profile', $response->getTargetUrl());
        self::assertSame(FlashResponse::FLASH_SUCCESS, $response->getFlash()->key());
    }

    public function getFormData(): array
    {
        return [
            'profile' => [
                'mobile' => '9876543210',
                'email' => 'test@example.com',
            ],
        ];
    }
}
