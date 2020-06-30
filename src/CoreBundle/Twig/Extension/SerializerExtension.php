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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @codeCoverageIgnore
 */
class SerializerExtension extends AbstractExtension
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('serialize', function ($data, string $format, array $groups = []) {
                return $this->serializer->serialize($data, $format, ['groups' => $groups]);
            }),
        ];
    }
}
