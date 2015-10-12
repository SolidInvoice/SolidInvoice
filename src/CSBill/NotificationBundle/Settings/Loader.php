<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
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
     * @var bool
     */
    private $hasHipchatConfig = false;

    /**
     * @var bool
     */
    private $hasSmsConfig = false;

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

            if (isset($value['hipchat'])) {
                $this->checkHipchatConfig();
            }

            if (isset($value['sms'])) {
                $this->checkSmsConfig();
            }

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
            /* @var \CSBill\NotificationBundle\Entity\Notification $value */

            $setting = new Setting();
            $setting->setKey($value->getEvent())
                ->setValue(array(
                    'email' => $value->getEmail(),
                    'hipchat' => $value->getHipchat(),
                    'sms' => $value->getSms(),
                ))
                ->setType('notification');

            $settings['notification'][$value->getEvent()] = $setting;
        }

        return $settings;
    }

    /**
     * Check if we can connect to the database and if the tables are loaded.
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

    /**
     * @throws \Exception
     */
    private function checkHipchatConfig()
    {
        if (true === $this->hasHipchatConfig) {
            return;
        }

        /** @var EntityRepository $repository */
        $repository = $this->manager->getRepository('CSBillSettingsBundle:Setting');

        $builder = $repository->createQueryBuilder('s');

        $builder
            ->select('s.key, s.value')
            ->join('s.section', 'se')
            ->where('se.name = :section')
            ->setParameter('section', 'hipchat');

        $query = $builder->getQuery();

        foreach ($query->getArrayResult() as $result) {
            if ($result['key'] === 'auth_token' && null === $result['value']) {
                throw new \Exception('You need to set a HipChat Auth token in order to enable HipChat notifications');
            }

            if ($result['key'] === 'room_id' && null === $result['value']) {
                throw new \Exception('You need to set a HipChat Room ID in order to enable HipChat notifications');
            }
        }

        $this->hasHipchatConfig = true;
    }

    /**
     * @throws \Exception
     */
    private function checkSmsConfig()
    {
        if (true === $this->hasSmsConfig) {
            return;
        }

        /** @var EntityRepository $repository */
        $repository = $this->manager->getRepository('CSBillUserBundle:User');

        $builder = $repository->createQueryBuilder('u');

        $builder->select('COUNT(u.id)')
            ->where('u.mobile IS NOT NULL');

        $query = $builder->getQuery();

        $this->hasSmsConfig = (int) $query->getSingleScalarResult() > 0;

        if (false === $this->hasSmsConfig) {
            throw new \Exception(
                'You need at least one user with a mobile number in order to enable SMS notifications'
            );
        }
    }
}
