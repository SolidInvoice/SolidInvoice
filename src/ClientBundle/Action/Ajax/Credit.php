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

namespace SolidInvoice\ClientBundle\Action\Ajax;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use JsonException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Credit implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly CreditRepository $repository,
    ) {
    }

    public function get(Client $client): JsonResponse
    {
        return $this->toJson($client);
    }

    /**
     * @throws MathException
     * @throws JsonException
     */
    public function put(Request $request, Client $client): JsonResponse
    {
        $value = BigInteger::of(((json_decode($request->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR)['credit'] ?? 0) * 100));

        $this->repository->addCredit($client, $value);

        return $this->toJson($client);
    }

    /**
     * @throws MathException
     */
    private function toJson(Client $client): JsonResponse
    {
        return $this->json(
            [
                'credit' => MoneyFormatter::toFloat($client->getCredit()->getValue()),
                'id' => $client->getId(),
            ]
        );
    }
}
