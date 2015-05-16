<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace CSBill\NotificationBundle\Settings;

use CSBill\NotificationBundle\Entity\Notification;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Loader\SettingsLoaderInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;

class Loader implements SettingsLoaderInterface
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $sections;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->manager = $doctrine->getManager();
    }

    /**
     * @param array $settings
     */
    public function saveSettings(array $settings = array())
    {
        /** @var EntityRepository $repository */
        $repository = $this->manager->getRepository('CSBillNotificationBundle:Notification');

        /** @var Notification[] $values */
        $values = $repository->findAll();

        foreach ($values as $notification) {
            $value = $settings['notification'][$notification->getEvent()]->getValue();

            $notification->setEmail(isset($value['email']))
                ->setHipchat(isset($value['hipchat']))
                ->setSms(isset($value['sms']));

            $this->manager->persist($notification);
        }

        $this->manager->flush();
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        if (!$this->checkConnection()) {
            return array();
        }

        $settings = array('notification' => array());

        /** @var \CSBill\SettingsBundle\Repository\SettingsRepository $repository */
        $repository = $this->manager->getRepository('CSBillNotificationBundle:Notification');

        $values = $repository->findAll();

        foreach ($values as $value) {
            /** @var \CSBill\NotificationBundle\Entity\Notification $value */

            $setting = new Setting();
            $setting->setKey($value->getEvent())
                ->setValue(array(
                    'email' => $value->getEmail(),
                    'hipchat' => $value->getHipchat(),
                    'sms' => $value->getSms()
                ))
                ->setType('notification');

            $settings['notification'][$value->getEvent()] = $setting;
        }

        return $settings;
    }

    /**
     * Check if we can connect to the database and if the tables are loaded
     *
     * @return bool
     */
    protected function checkConnection()
    {
        try {
            /** @var \CSBill\SettingsBundle\Repository\SectionRepository $repository */
            $repository = $this->manager->getRepository('CSBillSettingsBundle:Section');
            $this->sections = $repository->getTopLevelSections();
        } catch (DBALException $e) {
            return false;
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }
}