<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\DataFixtures\ORM;

use CSBill\SettingsBundle\Entity\Section;
use CSBill\SettingsBundle\Entity\Setting;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSettings extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\DependencyInjection.ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load settings into the database
     *
     * @param array  $settings
     * @param string $reference
     */
    protected function loadSettingValues(array $settings = array(), $reference = null)
    {
        if (!empty($settings)) {
            foreach ($settings as $section => $setting) {
                if (!is_array($setting)) {
                    continue;
                }

                $referenceKey = implode('.', array_filter(array($reference, $section)));

                if (isset($setting['settings']) && !empty($setting['settings'])) {
                    $referenceObject = $this->getReference('settings.'.$referenceKey);

                    $this->saveSettings($setting['settings'], $referenceObject);
                }

                if (isset($setting['children'])) {
                    $this->loadSettingValues(
                        $setting['children'],
                        implode('.', array_filter(array($reference, $section)))
                    );
                }
            }
        }
    }

    /**
     * Save the settings to the db
     *
     * @param array   $settings
     * @param Section $section
     */
    protected function saveSettings(array $settings, Section $section)
    {
        if (!empty($settings)) {
            foreach ($settings as $setting) {
                $entity = new Setting();

                $entity->setKey($setting['name']);

                if (isset($setting['value'])) {
                    $entity->setValue($setting['value']);
                }

                if (isset($setting['type'])) {
                    $entity->setType($setting['type']);
                }

                if (isset($setting['options'])) {
                    $entity->setOptions($setting['options']);
                }

                if (isset($setting['description'])) {
                    $entity->setDescription($setting['description']);
                }

                $entity->setSection($section);

                $this->em->persist($entity);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

        $settings = $this->container->getParameter('system.settings.default');

        $this->loadSettingValues($settings);

        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
