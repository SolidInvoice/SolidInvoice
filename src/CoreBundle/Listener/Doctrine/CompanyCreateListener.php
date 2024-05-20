<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Listener\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\CoreBundle\Company\DefaultData;
use SolidInvoice\CoreBundle\Entity\Company;

final class CompanyCreateListener implements EventSubscriber
{
    private DefaultData $defaultData;

    public function __construct(DefaultData $defaultData)
    {
        $this->defaultData = $defaultData;
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Company) {
            $this->defaultData->__invoke($entity, ['currency' => $entity->defaultCurrency ?? '']);
        }
    }
}
