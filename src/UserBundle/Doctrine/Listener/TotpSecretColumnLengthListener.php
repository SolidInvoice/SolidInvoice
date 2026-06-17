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

namespace SolidInvoice\UserBundle\Doctrine\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\UserBundle\Entity\User;

/**
 * Overrides the ORM column length for the totp_secret column on the User entity.
 *
 * The UserTwoFactor trait (from solidworx/platform) declares totp_secret with
 * length 45, but scheb/2fa-totp v7+ generates 52-character Base32 secrets
 * (Base32(random_bytes(32))). This listener raises the mapped length to 64 so
 * that doctrine:schema:update and doctrine:migrations:diff agree with the DB
 * column produced by migration Version30000_12.
 */
#[AsDoctrineListener(Events::loadClassMetadata)]
final class TotpSecretColumnLengthListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $classMetadata = $event->getClassMetadata();

        if ($classMetadata->getName() !== User::class) {
            return;
        }

        if (isset($classMetadata->fieldMappings['totpSecret'])) {
            $classMetadata->fieldMappings['totpSecret']['length'] = 64;
        }
    }
}
