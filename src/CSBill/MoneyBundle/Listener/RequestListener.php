<?php

namespace CSBill\MoneyBundle\Listener;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\MoneyBundle\Doctrine\Hydrator\MoneyHydrator;
use CSBill\MoneyBundle\Doctrine\Types\MoneyType;
use CSBill\MoneyBundle\Currency;
use CSBill\MoneyBundle\Entity\Money;
use CSBill\QuoteBundle\Entity\Quote;
use Money\Currency as MoneyCurrency;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelRequest(FilterControllerEvent $event)
    {
	foreach ($event->getRequest()->attributes->all() as $key => $value) {
	    if ($value instanceof Invoice || $value instanceof Quote) {
		$value = $value->getClient();
	    }

	    if ($value instanceof Client) {
		$currency = $value->getCurrency();

		if (null !== $currency) {
		    Money::setBaseCurrency($currency);

		    $currency = new MoneyCurrency($currency);

		    MoneyType::setCurrency($currency);
		    MoneyHydrator::setCurrency($currency);
		    Currency::setClientCurrency($value);

		    return;
		}
	    }
	}
    }
}