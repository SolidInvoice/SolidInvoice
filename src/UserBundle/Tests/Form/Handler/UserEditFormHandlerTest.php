<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Tests\Form\Handler;

use Mockery as M;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Handler\UserEditFormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserEditFormHandlerTest extends FormHandlerTestCase
{
    private $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = M::mock(RouterInterface::class);

        $this->router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->with('_users_list')
            ->andReturn('/users');
    }

    public function getHandler()
    {
        $handler = new UserEditFormHandler(new UserPasswordEncoder(new EncoderFactory([User::class => new BCryptPasswordEncoder(10)])), $this->router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertCount(1, $this->em->getRepository(User::class)->findAll());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('test', $data->getUserName());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    protected function getHandlerOptions(): array
    {
        $user = new User();
        $user->setUsername('one');
        $user->setPassword('one');
        $user->setPlainPassword('one');
        $user->setEmail('one@two.com');

        return [
            'user' => $user,
        ];
    }

    public function getFormData(): array
    {
        $password = $this->faker->password;

        return [
            'fos_user_registration_form' => [
                'username' => 'test',
                'email' => $this->faker->email,
                'plainPassword' => [
                    'first' => $password,
                    'second' => $password,
                ],
                'mobile' => $this->faker->phoneNumber,
                'enabled' => true,
            ],
        ];
    }
}
