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

namespace SolidInvoice\SettingsBundle\Tests\Form\Handler;

use Mockery as M;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\FormBundle\Test\FormHandlerTestCase;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Handler\SettingsFormHandler;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SettingsFormHandlerTest extends FormHandlerTestCase
{
    use DoctrineTestTrait;

    protected function setUp(): void
    {
        static::bootKernel();
    }

    public function getHandler(): SettingsFormHandler
    {
        $repository = $this->registry->getRepository(Setting::class);
        $router = M::mock(RouterInterface::class);
        $router->shouldReceive('generate')
            ->andReturn('/settings');

        return new SettingsFormHandler($repository, $router);
    }

    protected function beforeSuccess(FormRequest $form, $data): void
    {
        $form->getRequest()->attributes->set('_route', 'settings');
    }

    protected function assertOnSuccess(?Response $response, FormRequest $form, $data): void
    {
        self::assertSame([
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
                'sending_options' => [
                    'provider' => null,
                ],
            ],
            'sms' => [
                'twilio' => [
                    'number' => null,
                    'sid' => null,
                    'token' => null,
                ],
            ],
            'notification' => [
                'client_create' => '{"email":false,"sms":false}',
                'invoice_status_update' => '{"email":false,"sms":false}',
                'quote_status_update' => '{"email":false,"sms":false}',
                'payment_made' => '{"email":false,"sms":false}',
            ],
        ], $data);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertCount(1, $response->getFlash());
    }

    protected function assertResponse(FormRequest $formRequest): void
    {
        self::assertInstanceOf(Template::class, $formRequest->getResponse());
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

    protected function getTypes(): array
    {
        $extensions = parent::getTypes();

        $extensions[] = new MailTransportType([]);

        return $extensions;
    }
}
