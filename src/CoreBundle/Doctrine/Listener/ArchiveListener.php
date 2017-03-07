<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Doctrine\Listener;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;

class ArchiveListener extends MappedEventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
	return [
	    'loadClassMetadata',
	];
    }

    /**
     * Maps additional metadata.
     *
     * @param EventArgs $eventArgs
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
	$ea = $this->getEventAdapter($eventArgs);
	$this->loadMetadataForObjectClass($ea->getObjectManager(), $this->loadClassMetadata($eventArgs));
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
	return __NAMESPACE__;
    }
}
