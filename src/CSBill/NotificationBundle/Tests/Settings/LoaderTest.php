<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Tests\Settings;

use CSBill\CoreBundle\Tests\KernelAwareTest;
use CSBill\NotificationBundle\Entity\Notification;
use CSBill\NotificationBundle\Settings\Loader;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class LoaderTest extends KernelAwareTest
{
    public function testGetSettings()
    {
        $settingsLoader = new Loader($this->container->get('doctrine'));

        $settings = $settingsLoader->getSettings();

        $clientCreate = (new Setting())
            ->setKey('client_create')
            ->setValue(array('email' => true, 'hipchat' => false, 'sms' => false))
            ->setType('notification');

        $invoiceStatus = (new Setting())
            ->setKey('invoice_status_update')
            ->setValue(array('email' => true, 'hipchat' => false, 'sms' => false))
            ->setType('notification');

        $quoteStatus = (new Setting())
            ->setKey('quote_status_update')
            ->setValue(array('email' => true, 'hipchat' => false, 'sms' => false))
            ->setType('notification');

        $paymentMade = (new Setting())
            ->setKey('payment_made')
            ->setValue(array('email' => true, 'hipchat' => false, 'sms' => false))
            ->setType('notification');

        $values = array(
            'notification' => array(
                'client_create' => $clientCreate,
                'invoice_status_update' => $invoiceStatus,
                'quote_status_update' => $quoteStatus,
                'payment_made' => $paymentMade,
            ),
        );

        $this->assertEquals($values, $settings);
    }

    /**
     * @param array $settings
     * @dataProvider settingsDataProvider
     */
    public function testSaveSettings(array $settings)
    {
        $doctrine = $this->container->get('doctrine');
        $manager = $doctrine->getManager();

        $user = new User();
        $user->setEmail('a@b.com')
            ->setMobile('+1234567890')
            ->setUsername('admin')
            ->setPassword('admin')
        ;

        /** @var EntityRepository $setting */
        $settingsRepo = $manager->getRepository('CSBillSettingsBundle:Setting');

        $settingsRepo
            ->createQueryBuilder('s')
            ->update()
            ->set('s.value', ':value')
            ->where('s.key = :key')
            ->setParameter('key', 'room_id')
            ->setParameter('value', 12345)
            ->getQuery()
            ->execute()
        ;

        $settingsRepo
            ->createQueryBuilder('s')
            ->update()
            ->set('s.value', ':value')
            ->where('s.key = :key')
            ->setParameter('key', 'auth_token')
            ->setParameter('value', 'abcdef')
            ->getQuery()
            ->execute()
        ;

        $manager->persist($user);
        $manager->flush();

        $settingsLoader = new Loader($doctrine);

        $settingsLoader->saveSettings($settings);

        $allSettings = $doctrine->getManager()
            ->getRepository('CSBillNotificationBundle:Notification')
            ->findAll();

        /** @var Notification $setting */
        foreach ($allSettings as $setting) {
            $this->assertFalse($setting->getEmail());
            $this->assertTrue($setting->getHipchat());
            $this->assertTrue($setting->getSms());
        }
    }

    /**
     * @param array $settings
     * @dataProvider settingsDataProvider
     */
    public function testSaveSettingsInvalidHipchatToken(array $settings)
    {
        $doctrine = $this->container->get('doctrine');

        $manager = $doctrine->getManager();

        /** @var EntityRepository $setting */
        $settingsRepo = $manager->getRepository('CSBillSettingsBundle:Setting');
        $settingsRepo
            ->createQueryBuilder('s')
            ->update()
            ->set('s.value', ':value')
            ->where('s.key = :key')
            ->setParameter('key', 'room_id')
            ->setParameter('value', 12345)
            ->getQuery()
            ->execute()
        ;

        $this->setExpectedException('Exception', 'You need to set a HipChat Auth token in order to enable HipChat notifications');

        $settingsLoader = new Loader($doctrine);

        $settingsLoader->saveSettings($settings);
    }

    /**
     * @param array $settings
     * @dataProvider settingsDataProvider
     */
    public function testSaveSettingsInvalidHipchatRoomId(array $settings)
    {
        $doctrine = $this->container->get('doctrine');

        $manager = $doctrine->getManager();
        /** @var EntityRepository $setting */
        $settingsRepo = $manager->getRepository('CSBillSettingsBundle:Setting');
        $settingsRepo
            ->createQueryBuilder('s')
            ->update()
            ->set('s.value', ':value')
            ->where('s.key = :key')
            ->setParameter('key', 'auth_token')
            ->setParameter('value', 'ABCDEF')
            ->getQuery()
            ->execute()
        ;

        $this->setExpectedException('Exception', 'You need to set a HipChat Room ID in order to enable HipChat notifications');

        $settingsLoader = new Loader($doctrine);

        $settingsLoader->saveSettings($settings);
    }

    public function tearDown()
    {
        $doctrine = $this->container->get('doctrine');

        $manager = $doctrine->getManager();
        $users = $manager
            ->getRepository('CSBillUserBundle:User')
            ->findAll();

        array_walk($users, array($manager, 'remove'));
        $manager->flush();

        /** @var EntityRepository $repo */
        $repo = $manager->getRepository('CSBillNotificationBundle:Notification');

        /** @var EntityRepository $setting */
        $setting = $manager->getRepository('CSBillSettingsBundle:Setting');

        $repo->createQueryBuilder('n')
            ->update()
            ->set('n.email', 1)
            ->set('n.hipchat', 0)
            ->set('n.sms', 0)
            ->getQuery()
            ->execute()
        ;

        $qb = $setting->createQueryBuilder('s');
        $qb
            ->update()
            ->set('s.value', ':value')
            ->where($qb->expr()->in('s.key', array('auth_token', 'room_id')))
            ->setParameter('value', null)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return array
     */
    public function settingsDataProvider()
    {
        $clientCreate = (new Setting())
            ->setKey('client_create')
            ->setValue(array(
                'hipchat' => true,
                'sms' => true,
            ));

        $invoiceStatus = (new Setting())
            ->setKey('invoice_status_update')
            ->setValue(array(
                'hipchat' => true,
                'sms' => true,
            ));

        $quoteStatus = (new Setting())
            ->setKey('quote_status_update')
            ->setValue(array(
                'hipchat' => true,
                'sms' => true,
            ));

        $paymentMade = (new Setting())
            ->setKey('payment_made')
            ->setValue(array(
                'hipchat' => true,
                'sms' => true,
            ));

        $settings = array(
            'notification' => array(
                'client_create' => $clientCreate,
                'invoice_status_update' => $invoiceStatus,
                'quote_status_update' => $quoteStatus,
                'payment_made' => $paymentMade,
            ),
        );

        return array(array($settings));
    }
}
