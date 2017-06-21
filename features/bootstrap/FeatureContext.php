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

use Behat\Behat\Context\Context;
use CSBill\ClientBundle\Entity\ContactType;
use CSBill\CoreBundle\CSBillCoreBundle;
use CSBill\CoreBundle\Entity\Version;
use CSBill\SettingsBundle\Entity\Setting;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FeatureContext implements Context
{
    private const DEFAULT_SETTINGS = [
        'system/general/app_name' => 'CSBill',
        'system/general/logo' => null,
        'quote/email_subject' => 'New Quotation - #{id}',
        'quote/bcc_address' => null,
        'invoice/email_subject' => 'New Invoice - #{id}',
        'invoice/bcc_address' => null,
        'email/from_name' => 'CSBill',
        'email/from_address' => 'info@csbill.org',
        'email/format' => 'both',
        'hipchat/auth_token' => null,
        'hipchat/room_id' => null,
        'hipchat/server_url' => 'https://api.hipchat.com',
        'hipchat/notify' => null,
        'hipchat/message_color' => 'yellow',
        'sms/twilio/number' => null,
        'sms/twilio/sid' => null,
        'sms/twilio/token' => null,
        'notification/client_create' => '{"email":true,"hipchat":false,"sms":false}',
        'notification/invoice_status_update' => '{"email":true,"hipchat":false,"sms":false}',
        'notification/quote_status_update' => '{"email":true,"hipchat":false,"sms":false}',
        'notification/payment_made' => '{"email":true,"hipchat":false,"sms":false}',
        'email/sending_options/transport' => 'sendmail',
        'email/sending_options/host' => null,
        'email/sending_options/user' => null,
        'email/sending_options/password' => null,
        'email/sending_options/port' => null,
        'email/sending_options/encryption' => null,
        'system/general/currency' => 'USD',
    ];

    /**
     * @var ManagerRegistry
     */
    private static $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        self::$doctrine = $doctrine;
    }

    /**
     * @AfterFeature
     */
    public static function resetDatabase()
    {
        foreach (self::$doctrine->getManagers() as $entityManager) {
            $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

            if (!empty($metadata)) {
                $tool = new SchemaTool($entityManager);
                $tool->dropSchema($metadata);
                $tool->createSchema($metadata);
            }
        }

        $em = self::$doctrine->getManagerForClass(Version::class);

        $em->persist((new Version())->setVersion(CSBillCoreBundle::VERSION));

        foreach (self::DEFAULT_SETTINGS as $key => $value) {
            $em->persist((new Setting())->setKey($key)->setValue($value)->setType(TextType::class));
        }

        foreach (['address', 'email', 'phone', 'mobile'] as $type) {
            $em->persist((new ContactType())->setType($type)->setName($type)->setRequired(false));
        }

        $em->flush();
    }
}
