<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Generator\BillingIdGenerator;

interface IdGeneratorInterface
{
    public function generate(object $entity, string $field): string;
}
