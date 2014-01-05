<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\SettingsBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CS\SettingsBundle\Entity\Setting;
use CS\SettingsBundle\Entity\Section;

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
                    $this->loadSettingValues($setting['children'], implode('.', array_filter(array($reference, $section))));
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
                $entity->setKey($setting['key']);

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
