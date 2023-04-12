<?php
declare(strict_types=1);

namespace SolidInvoice\PaymentBundle\Payum\Storage;

use LogicException;
use Payum\Core\Bridge\Doctrine\Storage\DoctrineStorage as BaseDoctrineStorage;
use Payum\Core\Model\Identity;
use Ramsey\Uuid\UuidInterface;
use function array_shift;
use function count;
use function get_class;

/**
 * Overwrite the default DoctrineStorage to support UUIDs.
 * Ramsey/Uuid serializes to a binary string, which causes an error when trying
 * to save the value to the database.
 */
final class DoctrineStorage extends BaseDoctrineStorage
{
    protected function doGetIdentity($model): Identity
    {
        $modelMetadata = $this->objectManager->getClassMetadata(get_class($model));
        $id = $modelMetadata->getIdentifierValues($model);
        if (count($id) > 1) {
            throw new LogicException('Storage does not support composite primary ids');
        }

        $modelId = array_shift($id);

        if ($modelId instanceof UuidInterface) {
            $modelId = $modelId->toString();
        }

        return new Identity($modelId, $model);
    }
}
