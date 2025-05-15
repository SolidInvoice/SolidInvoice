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

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use JsonException;
use Psr\EventDispatcher\EventDispatcherInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Company\DefaultData;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Event\CompanyCreatedEvent;

#[AsEntityListener(Events::postPersist, entity: Company::class)]
final readonly class CompanyCreatedListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private DefaultData $defaultData,
        private CompanySelector $companySelector,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function postPersist(Company $company): void
    {
        $this->eventDispatcher->dispatch(new CompanyCreatedEvent($company));

        $this->companySelector->switchCompany($company->getId());

        /** @TODO: Need a different way to specify the currency and not add it to the company entity */
        ($this->defaultData)($company, ['currency' => $company->currency]);
    }
}
