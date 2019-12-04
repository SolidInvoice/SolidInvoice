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

use FOS\UserBundle\Form\Factory\FormFactory;
use FOS\UserBundle\Form\Type\RegistrationFormType;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\Canonicalizer;
use FOS\UserBundle\Util\PasswordUpdater;
use Mockery as M;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Handler\UserAddFormHandler;
use SolidInvoice\UserBundle\Manager\UserManager;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserAddFormHandlerTest extends FormHandlerTestCase
{
    private $router;

    protected function setUp()
    {
        parent::setUp();

        $this->router = M::mock(RouterInterface::class);

        $this->router->shouldReceive('generate')
            ->zeroOrMoreTimes()
            ->with('_users_list')
            ->andReturn('/users');
    }

    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $handler = new UserAddFormHandler(new UserPasswordEncoder(new EncoderFactory([User::class => new BCryptPasswordEncoder(10)])), $this->router);
        $handler->setDoctrine($this->registry);

        return $handler;
    }

    protected function getHandlerOptions(): array
    {
        return [
            'user' => new User(),
        ];
    }

    /**
     * @param Response|null $response
     * @param mixed         $data
     * @param FormRequest   $form
     *
     * @throws \PHPUnit\Framework\Exception
     */
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

    protected function getExtensions(): array
    {
        $type = new RegistrationFormType(User::class);

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @return array
     */
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
