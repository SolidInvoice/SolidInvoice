<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\CompanyType;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use SolidInvoice\TaxBundle\Form\Type\TaxNumberType;
use SolidInvoice\SettingsBundle\Form\Type\AddressType;
use SolidInvoice\NotificationBundle\Form\Type\NotificationType;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use function assert;

final class CompanyFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    use SaveableTrait;

    private CompanySelector $companySelector;
    private RouterInterface $router;
    private Security $security;
    private ManagerRegistry $registry;

    public function __construct(
        Security $security,
        CompanySelector $companySelector,
        RouterInterface $router,
        ManagerRegistry $registry
    ) {
        $this->security = $security;
        $this->companySelector = $companySelector;
        $this->router = $router;
        $this->registry = $registry;
    }

    public function getForm(FormFactoryInterface $factory, Options $options): string
    {
        return CompanyType::class;
    }

    public function getResponse(FormRequest $formRequest): Template
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        return new Template(
            '@SolidInvoiceCore/Company/create.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'allowCancel' => !$user->getCompanies()->isEmpty(),
            ]
        );
    }

    public function onSuccess(FormRequest $form, $data): ?Response
    {
        $user = $this->security->getUser();
        assert($user instanceof User);

        $company = new Company();
        $company->setName($data['name']);
        $company->addUser($user);

        $this->save($company);

        $this->companySelector->switchCompany($company->getId());

        // @TODO: Default system config should move somewhere else
        $appConfig = [
            ['setting_key' => 'system/company/company_name', 'setting_value' => $company->getName(), 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'system/company/logo', 'setting_value' => null, 'description' => null, 'field_type' => ImageUploadType::class],
            ['setting_key' => 'quote/email_subject', 'setting_value' => 'New Quotation - #{id}', 'description' => 'To include the id of the quote in the subject, add the placeholder {id} where you want the id', 'field_type' => TextType::class],
            ['setting_key' => 'quote/bcc_address', 'setting_value' => null, 'description' => 'Send BCC copy of quote to this address', 'field_type' => EmailType::class],
            ['setting_key' => 'invoice/email_subject', 'setting_value' => 'New Invoice - #{id}', 'description' => 'To include the id of the invoice in the subject, add the placeholder {id} where you want the id', 'field_type' => TextType::class],
            ['setting_key' => 'invoice/bcc_address', 'setting_value' => null, 'description' => 'Send BCC copy of invoice to this address', 'field_type' => EmailType::class],
            ['setting_key' => 'email/from_name', 'setting_value' => $company->getName(), 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'email/from_address', 'setting_value' => 'info@solidinvoice.co', 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'sms/twilio/number', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'sms/twilio/sid', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'sms/twilio/token', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'system/company/vat_number', 'setting_value' => null, 'description' => null, 'field_type' => TaxNumberType::class],
            ['setting_key' => 'system/company/contact_details/email', 'setting_value' => null, 'description' => null, 'field_type' => EmailType::class],
            ['setting_key' => 'system/company/contact_details/phone_number', 'setting_value' => null, 'description' => null, 'field_type' => TextType::class],
            ['setting_key' => 'system/company/contact_details/address', 'setting_value' => null, 'description' => null, 'field_type' => AddressType::class],
            ['setting_key' => 'notification/client_create', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'notification/invoice_status_update', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'notification/quote_status_update', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'notification/payment_made', 'setting_value' => '{"email":true,"sms":false}', 'description' => null, 'field_type' => NotificationType::class],
            ['setting_key' => 'email/sending_options/provider', 'setting_value' => null, 'description' => null, 'field_type' => MailTransportType::class],
            ['setting_key' => 'system/company/currency', 'setting_value' => $data['currency'], 'description' => null, 'field_type' => CurrencyType::class],
        ];

        $em = $this->registry->getManager();

        foreach ($appConfig as $setting) {
            $settingEntity = new Setting();
            $settingEntity->setKey($setting['setting_key']);
            $settingEntity->setValue($setting['setting_value']);
            $settingEntity->setDescription($setting['description']);
            $settingEntity->setType($setting['field_type']);

            $em->persist($settingEntity);
        }

        $em->flush();

        return new RedirectResponse($this->router->generate('_dashboard'));
    }
}
