<?php

namespace CSBill\MoneyBundle;

use CSBill\ClientBundle\Entity\Client;
use Money\Currency as MoneyCurrency;

class Currency
{
    /**
     * @var string
     */
    private $baseCurrency;

    /**
     * @var string
     */
    private static $clientCurrency;

    /**
     * Currency constructor.
     *
     * @param $baseCurrency
     */
    public function __construct($baseCurrency)
    {
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * @param Client $client
     */
    public static function setClientCurrency(Client $client)
    {
        self::$clientCurrency = $client->getCurrency();
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $currency = $this->getCurrency();

        return call_user_func_array([$currency, $method], $args);
    }

    /**
     * @return MoneyCurrency
     */
    public function getCurrency()
    {
        $currency = (null !== self::$clientCurrency) ? self::$clientCurrency : $this->baseCurrency;

        return new MoneyCurrency($currency);
    }
}