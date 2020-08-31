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

namespace SolidInvoice\SettingsBundle\Form\Type;

use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\GmailTransportConfigType;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SesTransportConfigType;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SmtpTransportConfigType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MailTransportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'transport',
            Select2Type::class, [
            'choices' => [
                'SMTP' => 'smtp',
                'Amazon SES (API)' => 'ses+api',
                'Amazon SES (SMTP)' => 'ses+smtp',
                'Gmail' => 'gmail+smtp',
                'MailChimp (API)' => 'mandrill+api',
                'MailChimp (SMTP)' => 'mandrill+smtps',
                'Mailgun (API)' => 'mailgun+api',
                'Mailgun (SMTP)' => 'mailgun+smtp',
                'Postmark (API)' => 'postmark+api',
                'Postmark (SMTP)' => 'postmark+smtp',
                'SendGrid (API)' => 'sendgrid+api',
                'SendGrid (SMTP)' => 'sendgrid+smtp',
            ],
            'placeholder' => 'Choose Mail Provider',
            'label' => 'Mail Provider',
        ]);

        $configOptions = ['attr' => ['class' => 'd-none']];
        $builder->add('smtpConfig', SmtpTransportConfigType::class, $configOptions);
        $builder->add('sesConfig', SesTransportConfigType::class, $configOptions);
        $builder->add('gmailConfig', GmailTransportConfigType::class, $configOptions);
        $builder->add('mandrillConfig', GmailTransportConfigType::class, $configOptions);
        $builder->add('postmarkConfig', GmailTransportConfigType::class, $configOptions);
        $builder->add('sendgridConfig', GmailTransportConfigType::class, $configOptions);
    }
}
