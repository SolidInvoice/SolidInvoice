<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaymentExtension extends AbstractExtension
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PaymentMethodRepository
     */
    private $repository;

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(ManagerRegistry $registry, Currency $currency)
    {
        $this->registry = $registry;
        $this->currency = $currency;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('payment_enabled', [$this, 'paymentEnabled']),
            new TwigFunction('payments_configured', [$this, 'paymentConfigured']),

            new TwigFunction('total_income', [$this, 'getTotalIncome']),
            new TwigFunction('total_outstanding', [$this, 'getTotalOutstanding']),
        ];
    }

    public function getTotalIncome(Client $client = null): Money
    {
        $income = $this->registry->getRepository(Payment::class)->getTotalIncome($client);

        return new Money($income, $client->getCurrency() ?: $this->currency);
    }

    public function getTotalOutstanding(Client $client = null): Money
    {
        $outstanding = $this->registry->getRepository(Invoice::class)->getTotalOutstanding($client);

        return new Money($outstanding, $client->getCurrency() ?: $this->currency);
    }

    /**
     * @param string $method
     */
    public function paymentEnabled($method): bool
    {
        $paymentMethod = $this->getRepository()->findOneBy(['gatewayName' => $method]);

        if (null === $paymentMethod) {
            return false;
        }

        return $paymentMethod->isEnabled();
    }

    public function getRepository(): PaymentMethodRepository
    {
        if (null === $this->repository) {
            $this->repository = $this->registry->getRepository(PaymentMethod::class);
        }

        return $this->repository;
    }

    public function paymentConfigured(bool $includeInternal = true): int
    {
        return $this->getRepository()->getTotalMethodsConfigured($includeInternal);
    }

    public function getName(): string
    {
        return 'payment_extension';
    }
}
