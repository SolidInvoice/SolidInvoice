<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Test\Factory;

use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Repository\AdditionalContactDetailRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<AdditionalContactDetail>
 *
 * @method static AdditionalContactDetail|Proxy createOne(array $attributes = [])
 * @method static AdditionalContactDetail[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static AdditionalContactDetail[]|Proxy[] createSequence(array|callable $sequence)
 * @method static AdditionalContactDetail|Proxy find(object|array|mixed $criteria)
 * @method static AdditionalContactDetail|Proxy findOrCreate(array $attributes)
 * @method static AdditionalContactDetail|Proxy first(string $sortedField = 'id')
 * @method static AdditionalContactDetail|Proxy last(string $sortedField = 'id')
 * @method static AdditionalContactDetail|Proxy random(array $attributes = [])
 * @method static AdditionalContactDetail|Proxy randomOrCreate(array $attributes = [])
 * @method static AdditionalContactDetail[]|Proxy[] all()
 * @method static AdditionalContactDetail[]|Proxy[] findBy(array $attributes)
 * @method static AdditionalContactDetail[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static AdditionalContactDetail[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static AdditionalContactDetailRepository|RepositoryProxy repository()
 * @method AdditionalContactDetail|Proxy create(array|callable $attributes = [])
 */
final class AdditionalContactDetailFactory extends ModelFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaults(): array
    {
        $contactType = ContactTypeFactory::createOne();

        switch ($contactType->getName()) {
            case 'email':
                $value = self::faker()->email();
                break;
            case 'phone':
            case 'mobile':
            case 'fax':
                $value = self::faker()->phoneNumber();
                break;
            case 'website':
                $value = self::faker()->url();
                break;
            default:
                $value = self::faker()->word();
                break;
        }

        return [
            'type' => $contactType,
            'value' => $value,
        ];
    }

    protected static function getClass(): string
    {
        return AdditionalContactDetail::class;
    }
}
