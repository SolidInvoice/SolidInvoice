<?php
/**
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\CoreBundle\DataFixtures;

use CSBill\CoreBundle\Entity\Status;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

abstract class AbstractStatusLoader implements FixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $statusClass = $this->getStatusClass();

        foreach ($this->getStatusList() as $status => $label) {
            /** @var Status $entity */
            $entity = new $statusClass();
            $entity->setName($status)
                ->setLabel($label);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * Get an array of all the statuses to load
     *
     * @return array
     */
    abstract public function getStatusList();

    /**
     * Get the name of the status class to load
     *
     * @return string
     */
    abstract public function getStatusClass();
}
