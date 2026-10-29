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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineMigrations\Version30200_10;
use Psr\Log\NullLogger;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Runs against whichever engine the suite is on, so db-tests.yml covers MySQL, MariaDB and PostgreSQL.
 */
final class Version30200_10Test extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testMovesThePhoneNumbersIntoTheContactAndDropsTheFields(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $contact = ContactFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company]);
        $untouched = ContactFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company]);

        // The company already has additional_email; phone and mobile as companies had them before.
        $fields = ['additional_email' => $em->getRepository(CustomField::class)->findOneBy(['fieldKey' => 'additional_email', 'company' => $this->company])];
        self::assertInstanceOf(CustomField::class, $fields['additional_email']);

        foreach (['phone', 'mobile'] as $position => $key) {
            $fields[$key] = new CustomField()
                ->setTarget(CustomFieldTarget::CONTACT)
                ->setLabel($key)
                ->setFieldKey($key)
                ->setType(CustomFieldType::TEXT)
                ->setPosition($position)
                ->setCompany($this->company);
            $em->persist($fields[$key]);
        }

        foreach (['phone' => ' 021 555 0100 ', 'mobile' => '+27 82 555 0199', 'additional_email' => 'ap@acme.test'] as $key => $value) {
            $em->persist(new CustomFieldValue()
                ->setField($fields[$key])
                ->setTarget(CustomFieldTarget::CONTACT)
                ->setTargetId($contact->getId())
                ->setValue($value)
                ->setCompany($this->company));
        }

        $em->flush();
        $em->clear();

        $connection = self::getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        $migration = new Version30200_10($connection, new NullLogger());
        $migration->postUp(new Schema());
        $migration->postUp(new Schema());

        $migrated = $em->find(Contact::class, $contact->getId());
        self::assertInstanceOf(Contact::class, $migrated);
        self::assertSame('021 555 0100', $migrated->getPhone());
        self::assertSame('+27 82 555 0199', $migrated->getMobile());

        $other = $em->find(Contact::class, $untouched->getId());
        self::assertInstanceOf(Contact::class, $other);
        self::assertNull($other->getPhone());

        self::assertSame(['additional_email'], $connection->fetchFirstColumn("SELECT field_key FROM custom_field WHERE target = 'CONTACT'"));
        self::assertSame(['ap@acme.test'], $connection->fetchFirstColumn('SELECT ' . $connection->quoteSingleIdentifier('value') . ' FROM custom_field_value'));
    }
}
