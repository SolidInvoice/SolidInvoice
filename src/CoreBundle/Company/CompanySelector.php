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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;
use function assert;

final class CompanySelector
{
    private ?UuidInterface $companyId = null;

    private readonly OrderedTimeCodec $codec;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
        $factory = clone Uuid::getFactory();
        assert($factory instanceof UuidFactory);

        $this->codec = new OrderedTimeCodec($factory->getUuidBuilder());
    }

    public function getCompany(): ?UuidInterface
    {
        return $this->companyId;
    }

    public function switchCompany(UuidInterface $companyId): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $companyIdBytes = $this->codec->encodeBinary($companyId);

        $em
            ->getFilters()
            ->enable('company')
            ->setParameter('companyId', $companyIdBytes, Types::STRING);

        $this->companyId = $companyId;
    }
}
