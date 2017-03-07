<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Twig;

use CSBill\ClientBundle\Entity\Client;
use CSBill\PaymentBundle\Repository\PaymentMethodRepository;
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
    public function getFunctions()
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
    public function getTotalIncome(Client $client = null)
    {
	$income = $this->registry->getRepository('CSBillPaymentBundle:Payment')->getTotalIncome($client);

	return new Money($income, $client->getCurrency() ?: $this->currency);
    }

    /**
     * @param Client|null $client
     *
     * @return Money
     */
    public function getTotalOutstanding(Client $client = null)
    {
	$outstanding = $this->registry->getRepository('CSBillInvoiceBundle:Invoice')->getTotalOutstanding($client);

	return new Money($outstanding, $client->getCurrency() ?: $this->currency);
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function paymentEnabled($method)
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
    public function getRepository()
    {
	if (null === $this->repository) {
	    $this->repository = $this->registry->getRepository('CSBillPaymentBundle:PaymentMethod');
	}

	return $this->repository;
    }

    /**
     * @param bool $includeInternal
     *
     * @return int
     */
    public function paymentConfigured($includeInternal = true)
    {
	return $this->getRepository()->getTotalMethodsConfigured($includeInternal);
    }

    /**
     * @return string
     */
    public function getName()
    {
	return 'payment_extension';
    }
}
