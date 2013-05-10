<?php

/*
 * This file is part of the CSUserBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InvoiceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use CSBill\InvoiceBundle\Entity\Status;
use CSBill\InvoiceBundle\Model\Status as StatusModel;

class LoadStatus implements FixtureInterface
{
    protected $statusList = array( 'draft'     => 'draft',
                                   'pending'   => 'warning',
                                   'paid'      => 'success',
                                   'overdue'   => 'important',
                                   'cancelled' => 'inverse');

    public function load(ObjectManager $manager)
    {
        foreach ($this->statusList as $status => $label) {
            $entity = new Status;
            $entity->setName($status)
                   ->setLabel($label);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
