<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Doctrine\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;

class CompanyListener implements EventSubscriber
{
    private CompanySelector $companySelector;

    public function __construct(CompanySelector $companySelector)
    {
        $this->companySelector = $companySelector;
    }
    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    /**
     * Maps additional metadata.
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();
        $em = $eventArgs->getObjectManager();
        $metaData = $em->getClassMetadata(get_class($object));

        if ($metaData->hasAssociation('company')) {
            $repository = $em->getRepository(Company::class);
            $companyId = $this->companySelector->getCompany();

            if (null !== $companyId) {
                $object->setCompany($repository->find($companyId));
            }
        }
    }
}
