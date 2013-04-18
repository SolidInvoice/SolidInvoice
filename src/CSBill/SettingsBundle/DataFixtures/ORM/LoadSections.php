<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use CS\SettingsBundle\Entity\Section;

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
     * (non-PHPdoc)
     * @see Symfony\Component\DependencyInjection.ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = NULL)
    {
        $this->container = $container;
    }

    /**
     * Load sections into the database
     *
     * @param array $sections
     * @param Section $parent
     * @param string $reference
     */
    protected function loadSections(array $sections = array(), Section $parent = null, $reference = null)
    {
        if(!empty($sections)) {
            foreach($sections as $section => $settings) {

                $sectionEntity = new Section();
                $sectionEntity->setName($section);

                if($parent !== null) {
                    $sectionEntity->setParent($parent);
                }

                $referenceKey = implode('.', array_filter(array($reference, $section)));

                if(is_array($settings) && isset($settings['children'])) {
                    $this->loadSections($settings['children'], $sectionEntity, $referenceKey);
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

        $this->loadSections($defaultSettings);

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
