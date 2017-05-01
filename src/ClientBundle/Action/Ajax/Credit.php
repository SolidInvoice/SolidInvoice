<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Action\Ajax;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use Money\Money;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Credit
{
    use JsonTrait;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var CreditRepository
     */
    private $repository;

    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @param CreditRepository $repository
     * @param MoneyFormatter   $formatter
     * @param Currency         $currency
     */
    public function __construct(CreditRepository $repository, MoneyFormatter $formatter, Currency $currency)
    {
        $this->currency = $currency;
        $this->repository = $repository;
        $this->formatter = $formatter;
    }

    /**
     * @param Client $client
     *
     * @return JsonResponse
     */
    public function get(Client $client): JsonResponse
    {
        return $this->toJson($client);
    }

    /**
     * @param Request $request
     * @param Client  $client
     *
     * @return JsonResponse
     */
    public function put(Request $request, Client $client): JsonResponse
    {
        $value = new Money((int) ($request->request->get('credit') * 100), $client->getCurrency() ?? $this->currency);

        $this->repository->addCredit($client, $value);

        return $this->toJson($client);
    }

    private function toJson(Client $client)
    {
        return $this->json(
            [
                'credit' => $this->formatter->toFloat($client->getCredit()->getValue()),
                'id' => $client->getId(),
            ]
        );
    }
}
