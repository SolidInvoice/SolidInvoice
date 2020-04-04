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

namespace SolidInvoice\SettingsBundle\Tests\Form\Handler;

use Mockery as M;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Handler\SettingsFormHandler;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SettingsFormHandlerTest extends FormHandlerTestCase
{
    use DoctrineTestTrait;

    /**
     * @return string|FormHandlerInterface
     */
    public function getHandler()
    {
        $repository = $this->registry->getRepository(Setting::class);
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->andReturn('/settings');

        return new SettingsFormHandler($repository, $router);
    }

    protected function assertOnSuccess(?Response $response, $data, FormRequest $form)
    {
        $this->assertSame(
            [
                'system' => [
                    'company' => [
                        'company_name' => null,
                        'logo' => null,
                        'vat_number' => null,
                        'contact_details' => [
                            'email' => null,
                            'phone_number' => null,
                            'address' => '{"street1":null,"street2":null,"city":null,"state":null,"zip":null,"country":null}',
                        ],
                        'currency' => null,
                    ],
                ],
                'quote' => [
                    'email_subject' => null,
                    'bcc_address' => null,
                ],
                'invoice' => [
                    'email_subject' => null,
                    'bcc_address' => null,
                ],
                'email' => [
                    'from_name' => null,
                    'from_address' => null,
                    'format' => null,
                    'sending_options' => [
                        'transport' => null,
                        'host' => null,
                        'user' => null,
                        'password' => null,
                        'port' => null,
                        'encryption' => null,
                    ],
                ],
                'sms' => [
                    'twilio' => [
                        'number' => null,
                        'sid' => null,
                        'token' => null,
                    ],
                ],
                'design' => [
                    'system' => [
                        'theme' => null,
                    ],
                ],
                'notification' => [
                    'client_create' => '{"email":false,"sms":false}',
                    'invoice_status_update' => '{"email":false,"sms":false}',
                    'quote_status_update' => '{"email":false,"sms":false}',
                    'payment_made' => '{"email":false,"sms":false}',
                ],
            ],
            $data
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertCount(1, $response->getFlash());
    }

    protected function assertResponse(FormRequest $formRequest)
    {
        $this->assertInstanceOf(Template::class, $formRequest->getResponse());
    }

    public function getFormData(): array
    {
        return [
            'settings' => [
                'company' => [
                    'company_name' => 'four',
                ],
            ],
        ];
    }
}
