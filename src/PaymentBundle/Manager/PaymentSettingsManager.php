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

namespace SolidInvoice\PaymentBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;

class PaymentSettingsManager
{
    /**
     * @var PaymentMethodRepository
     */
    protected $repository;

    /**
     * @var array<string, array<string, string>>
     */
    private array $settings = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        /** @var PaymentMethodRepository $repository */
        $repository = $doctrine->getRepository(PaymentMethod::class);
        $this->repository = $repository;
    }

    /**
     * @return array<string, string>
     */
    public function get(string $paymentMethod): array
    {
        if (! isset($this->settings[$paymentMethod])) {
            $this->settings[$paymentMethod] = $this->repository->getSettingsForMethodArray($paymentMethod);
        }

        return $this->settings[$paymentMethod];
    }
}
