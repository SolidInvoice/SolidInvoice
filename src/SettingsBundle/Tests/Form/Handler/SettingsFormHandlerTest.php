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
use CSBill\FormBundle\Test\FormHandlerTestCase;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Form\Handler\SettingsFormHandler;
use CSBill\SettingsBundle\Manager\SettingsManager;
use CSBill\SettingsBundle\Tests\Fixtures\SettingsLoaderTest;
use Mockery as M;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

class SettingsFormHandlerTest extends FormHandlerTestCase
{
    private $settings;

    protected function setUp()
    {
        parent::setUp();

        $this->settings = new SettingsLoaderTest(
            [
                'one' => [
                    'two' => (new Setting())->setKey('two')->setType('password')->setValue('test'),
                ],
            ]
        );
    }

    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $settingsManager = new SettingsManager();
        $settingsManager->addSettingsLoader($this->settings);

        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->andReturn('/settings');

        $handler = new SettingsFormHandler($settingsManager, new Session(new MockArraySessionStorage()), $router);

        return $handler;
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertSame(['one' => ['two' => 'four']], $data);

        $this->assertEquals(
            [
                'one' => [
                    'two' => (new Setting())->setKey('two')->setType('password')->setValue('four'),
                ],
            ],
            $this->settings->getSettings()
        );
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    protected function getEntities(): array
    {
        return [];
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

    protected function getEntityNamespaces(): array
    {
        return [];
    }
}
