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
use Doctrine\Persistence\ObjectRepository;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;

class PaymentSettingsManager
{
    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var array
     */
    private $settings = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->repository = $doctrine->getRepository(PaymentMethod::class);
    }

    public function get(string $paymentMethod): array
    {
        if (! isset($this->settings[$paymentMethod])) {
            $this->settings[$paymentMethod] = $this->repository->getSettingsForMethodArray($paymentMethod);
        }

        return $this->settings[$paymentMethod];
    }
}
