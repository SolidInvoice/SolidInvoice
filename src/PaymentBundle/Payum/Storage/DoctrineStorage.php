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

namespace SolidInvoice\PaymentBundle\Payum\Storage;

use LogicException;
use Payum\Core\Bridge\Doctrine\Storage\DoctrineStorage as BaseDoctrineStorage;
use Payum\Core\Model\Identity;
use Symfony\Component\Uid\Ulid;
use function array_shift;
use function count;

/**
 * Overwrite the default DoctrineStorage to support UUIDs.
 * Ramsey/Uuid serializes to a binary string, which causes an error when trying
 * to save the value to the database.
 * @see \SolidInvoice\PaymentBundle\Tests\Payum\Storage\DoctrineStorageTest
 */
final class DoctrineStorage extends BaseDoctrineStorage
{
    protected function doGetIdentity($model): Identity
    {
        $modelMetadata = $this->objectManager->getClassMetadata($model::class);
        $id = $modelMetadata->getIdentifierValues($model);
        if (count($id) > 1) {
            throw new LogicException('Storage does not support composite primary ids');
        }

        $modelId = array_shift($id);

        if ($modelId instanceof Ulid) {
            $modelId = $modelId->toString();
        }

        return new Identity($modelId, $model);
    }
}
