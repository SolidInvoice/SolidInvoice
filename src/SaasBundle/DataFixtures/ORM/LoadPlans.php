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

namespace SolidInvoice\SaasBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use SolidWorx\Platform\SaasBundle\Entity\Plan;

/**
 * Seeds the four canonical SaaS plans (Free / Solo / Business / Agency).
 *
 * Plan IDs and prices are placeholders for dev/test only — production
 * billing identifiers are managed in the LemonSqueezy dashboard and
 * synced via webhooks. The Free plan uses price 0 and plan_id "0" so
 * `Plan::isFree()` returns true and checkout is skipped.
 *
 * @codeCoverageIgnore
 */
final class LoadPlans extends Fixture
{
    public const string REF_FREE = 'plan_free';

    public const string REF_SOLO = 'plan_solo';

    public const string REF_BUSINESS = 'plan_business';

    public const string REF_AGENCY = 'plan_agency';

    public function load(ObjectManager $manager): void
    {
        $free = new Plan()
            ->setName('Free')
            ->setPlanId('0')
            ->setPrice(0)
            ->setDescription('Free forever — basic invoicing for getting started.')
            ->setDefault(true)
            ->setActive(true);

        $solo = new Plan()
            ->setName('Solo')
            ->setPlanId('solo-monthly')
            ->setPrice(900)
            ->setDescription('Single freelancer with active client billing.')
            ->setActive(true);

        $business = new Plan()
            ->setName('Business')
            ->setPlanId('business-monthly')
            ->setPrice(1900)
            ->setDescription('Growing teams that need automation and branding.')
            ->setActive(true);

        $agency = new Plan()
            ->setName('Agency')
            ->setPlanId('agency-monthly')
            ->setPrice(3900)
            ->setDescription('Agencies running unlimited clients on custom domains.')
            ->setActive(true);

        foreach ([$free, $solo, $business, $agency] as $plan) {
            $manager->persist($plan);
        }

        $manager->flush();

        $this->addReference(self::REF_FREE, $free);
        $this->addReference(self::REF_SOLO, $solo);
        $this->addReference(self::REF_BUSINESS, $business);
        $this->addReference(self::REF_AGENCY, $agency);
    }
}
