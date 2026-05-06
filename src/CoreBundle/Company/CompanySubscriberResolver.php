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

namespace SolidInvoice\CoreBundle\Company;

use Override;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;

final readonly class CompanySubscriberResolver implements SubscriberResolver
{
    public function __construct(
        private CompanySelectorInterface $selector,
        private CompanyRepository $repository,
    ) {
    }

    #[Override]
    public function resolve(): ?SubscribableInterface
    {
        $id = $this->selector->getCompany();

        return $id === null ? null : $this->repository->find($id);
    }
}
