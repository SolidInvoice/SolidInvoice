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

namespace SolidInvoice\ApiBundle\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use function is_object;
use function is_string;
use function trim;

/**
 * The input DTO of an action endpoint whose body is optional. API Platform's own deserializer
 * insists on a Content-Type and a JSON document, so an empty POST would be a 400; here it is the
 * DTO's defaults. Pair it with `deserialize: false`. Validation still runs on what this returns.
 *
 * @implements ProviderInterface<object>
 */
final readonly class OptionalBodyProvider implements ProviderInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $class = $operation->getInput()['class'] ?? null;
        assert(is_string($class));

        $request = $context['request'] ?? null;
        $content = $request instanceof Request ? trim($request->getContent()) : '';

        if ('' === $content) {
            return new $class();
        }

        try {
            $input = $this->serializer->deserialize($content, $class, 'json', $operation->getDenormalizationContext() ?? []);
        } catch (ExceptionInterface $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        assert(is_object($input));

        return $input;
    }
}
