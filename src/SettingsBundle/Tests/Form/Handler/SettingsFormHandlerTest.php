<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Tests\Form\Handler;

use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Test\Traits\DoctrineTestTrait;
use CSBill\FormBundle\Test\FormHandlerTestCase;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Form\Handler\SettingsFormHandler;
use Mockery as M;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

class SettingsFormHandlerTest extends FormHandlerTestCase
{
    use DoctrineTestTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->setupDoctrine();

        $setting = new Setting();
        $setting->setKey('one/two');
        $setting->setValue('three');
        $setting->setType(TextType::class);

        $this->em->persist($setting);
        $this->em->flush();
    }

    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $repository = $this->em->getRepository('CSBillSettingsBundle:Setting');
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->andReturn('/settings');

        $handler = new SettingsFormHandler($repository, new Session(new MockArraySessionStorage()), $router);

        return $handler;
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertSame(['one' => ['two' => 'four']], $data);

        $setting = (new Setting())->setKey('one/two')->setType(TextType::class)->setValue('four');
        $property = new \ReflectionProperty(Setting::class, 'id');
        $property->setAccessible(true);
        $property->setValue($setting, 1);

        $this->assertEquals(
            [
                $setting
            ],
            $this->em->getRepository('CSBillSettingsBundle:Setting')->findAll()
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    /**
     * @return array
     */
    public function getFormData(): array
    {
        return [
            'settings' => [
                'one' => [
                    'two' => 'four',
                ],
            ],
        ];
    }

    protected function getEntities(): array
    {
        return [
            'CSBillSettingsBundle:Setting'
        ];
    }

    protected function getEntityNamespaces(): array
    {
        return [
            'CSBillSettingsBundle' => 'CSBill\\SettingsBundle\\Entity'
        ];
    }
}
