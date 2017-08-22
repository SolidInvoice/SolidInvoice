<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Twig;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;
use Twig_Extension;
use Twig_SimpleFunction;

class PaymentExtension extends Twig_Extension
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

    /**
     * @param ManagerRegistry $registry
     * @param Currency        $currency
     */
    public function __construct(ManagerRegistry $registry, Currency $currency)
    {
        $this->registry = $registry;
        $this->currency = $currency;
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new Twig_SimpleFunction('payment_enabled', [$this, 'paymentEnabled']),
            new Twig_SimpleFunction('payments_configured', [$this, 'paymentConfigured']),

            new Twig_SimpleFunction('total_income', [$this, 'getTotalIncome']),
            new Twig_SimpleFunction('total_outstanding', [$this, 'getTotalOutstanding']),
        ];
    }

    /**
     * @param Client|null $client
     *
     * @return Money
     */
    public function getTotalIncome(Client $client = null): Money
    {
        $income = $this->registry->getRepository('SolidInvoicePaymentBundle:Payment')->getTotalIncome($client);

        return new Money($income, $client->getCurrency() ?: $this->currency);
    }

    /**
     * @param Client|null $client
     *
     * @return Money
     */
    public function getTotalOutstanding(Client $client = null): Money
    {
        $outstanding = $this->registry->getRepository('SolidInvoiceInvoiceBundle:Invoice')->getTotalOutstanding($client);

        return new Money($outstanding, $client->getCurrency() ?: $this->currency);
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function paymentEnabled($method): bool
    {
        $paymentMethod = $this->getRepository()->findOneBy(['gatewayName' => $method]);

        if (null === $paymentMethod) {
            return false;
        }

        return $paymentMethod->isEnabled();
    }

    /**
     * @return PaymentMethodRepository
     */
    public function getRepository(): PaymentMethodRepository
    {
        if (null === $this->repository) {
            $this->repository = $this->registry->getRepository('SolidInvoicePaymentBundle:PaymentMethod');
        }

        return $this->repository;
    }

    /**
     * @param bool $includeInternal
     *
     * @return int
     */
    public function paymentConfigured(bool $includeInternal = true): int
    {
        return $this->getRepository()->getTotalMethodsConfigured($includeInternal);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'payment_extension';
    }
}
