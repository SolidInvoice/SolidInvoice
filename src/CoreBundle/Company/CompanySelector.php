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
use RuntimeException;
use SolidInvoice\SettingsBundle\SystemConfig;
use function assert;

final class CompanySelector
{
    private ManagerRegistry $registry;

    private ?UuidInterface $companyId = null;

    private OrderedTimeCodec $codec;

    private SystemConfig $config;

    public function __construct(ManagerRegistry $registry, SystemConfig $config)
    {
        $this->registry = $registry;

        $factory = clone Uuid::getFactory();
        assert($factory instanceof UuidFactory);

        $this->codec = new OrderedTimeCodec($factory->getUuidBuilder());
        $this->config = $config;
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
            ->setParameter('companyId', $companyIdBytes, Types::BINARY);

        $this->companyId = $companyId;

        try {
            Currency::set($this->config->getCurrency());
        } catch (RuntimeException $e) {
            // Currency is not set, so we can't set it here
        }
    }
}
