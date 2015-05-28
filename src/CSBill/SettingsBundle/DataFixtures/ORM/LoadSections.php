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
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSections extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
     * (non-PHPdoc).
     *
     * @see Symfony\Component\DependencyInjection.ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load sections into the database.
     *
     * @param array   $sections
     * @param Section $parent
     * @param string  $reference
     */
    protected function loadSettingsSections(array $sections = array(), Section $parent = null, $reference = null)
    {
        if (!empty($sections)) {
            foreach ($sections as $section => $settings) {
                $sectionEntity = new Section();
                $sectionEntity->setName($section);

                if ($parent !== null) {
                    $sectionEntity->setParent($parent);
                }

                $referenceKey = implode('.', array_filter(array($reference, $section)));

                if (is_array($settings) && isset($settings['children'])) {
                    $this->loadSettingsSections($settings['children'], $sectionEntity, $referenceKey);
                }

                $this->addReference('settings.'.$referenceKey, $sectionEntity);

                $this->em->persist($sectionEntity);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $defaultSettings = $this->container->getParameter('system.settings.default');

        $this->em = $manager;

        $this->loadSettingsSections($defaultSettings);

        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
