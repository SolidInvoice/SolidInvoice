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

use Liip\TestFixturesBundle\Test\FixturesTrait;
use Mockery as M;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\UserBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\UserBundle\Form\Handler\ProfileEditFormHandler;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileEditHandlerTest extends FormHandlerTestCase
{
    private $userRepository;

    private $tokenStorage;

    private $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = M::mock(UserRepository::class);
        $this->tokenStorage = M::mock(TokenStorageInterface::class);
        $this->router = M::mock(RouterInterface::class);

        $executor = (new KernelExecutor())();

        $this->tokenStorage->shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn(new AnonymousToken($this->faker->sha1, $executor->getReferenceRepository()->getReference('user2')));
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler()
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
        static::assertSame('9876543210', $data->getMobile());
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertInstanceOf(FlashResponse::class, $response);
        static::assertSame('/profile', $response->getTargetUrl());
        static::assertSame(FlashResponse::FLASH_SUCCESS, $response->getFlash()->key());
    }

    public function getFormData(): array
    {
        return [
            'profile' => [
                'mobile' => '9876543210',
            ],
        ];
    }
}

class KernelExecutor extends KernelTestCase
{
    use FixturesTrait;

    public function __invoke()
    {
        return $this->loadFixtures([LoadData::class], true);
    }

    public function __destruct()
    {
        $this->tearDown();
    }
}
