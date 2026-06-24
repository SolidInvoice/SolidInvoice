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

namespace SolidInvoice\ApiBundle\Tests\State\Processor;

use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\State\Processor\QuoteAutoUsersStateProcessor;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;

final class QuoteAutoUsersStateProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAutoAssignsClientContactsWhenUsersEmpty(): void
    {
        $contact1 = new Contact();
        $contact2 = new Contact();

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getContacts')
            ->once()
            ->andReturn(new ArrayCollection([$contact1, $contact2]));

        $quote = new Quote();
        $quote->setClient($client);

        $inner = Mockery::mock(ProcessorInterface::class);
        $inner->shouldReceive('process')
            ->once()
            ->with($quote, Mockery::type(Post::class), [], [])
            ->andReturn($quote);

        $processor = new QuoteAutoUsersStateProcessor($inner);
        $processor->process($quote, new Post(), [], []);

        self::assertCount(2, $quote->getUsers());
        self::assertTrue($quote->getUsers()->contains($contact1));
        self::assertTrue($quote->getUsers()->contains($contact2));
    }

    public function testDoesNotOverwriteExistingUsers(): void
    {
        $existingContact = new Contact();
        $quote = new Quote();
        $quote->addUser($existingContact);

        $inner = Mockery::mock(ProcessorInterface::class);
        $inner->shouldReceive('process')
            ->once()
            ->andReturn($quote);

        $processor = new QuoteAutoUsersStateProcessor($inner);
        $processor->process($quote, new Post(), [], []);

        self::assertCount(1, $quote->getUsers());
        self::assertTrue($quote->getUsers()->contains($existingContact));
    }

    public function testDoesNotAssignWhenClientIsNull(): void
    {
        $quote = new Quote();

        $inner = Mockery::mock(ProcessorInterface::class);
        $inner->shouldReceive('process')
            ->once()
            ->andReturn($quote);

        $processor = new QuoteAutoUsersStateProcessor($inner);
        $processor->process($quote, new Post(), [], []);

        self::assertCount(0, $quote->getUsers());
    }

    public function testNonPostOperationIsPassedThrough(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getContacts')->never();

        $quote = new Quote();
        $quote->setClient($client);

        $inner = Mockery::mock(ProcessorInterface::class);
        $inner->shouldReceive('process')
            ->once()
            ->andReturn($quote);

        $processor = new QuoteAutoUsersStateProcessor($inner);
        $processor->process($quote, new Patch(), [], []);

        self::assertCount(0, $quote->getUsers());
    }

    public function testNonQuoteDataIsPassedThrough(): void
    {
        $invoice = new Invoice();

        $inner = Mockery::mock(ProcessorInterface::class);
        $inner->shouldReceive('process')
            ->once()
            ->with($invoice, Mockery::type(Post::class), [], [])
            ->andReturn($invoice);

        $processor = new QuoteAutoUsersStateProcessor($inner);
        $result = $processor->process($invoice, new Post(), [], []);

        self::assertSame($invoice, $result);
    }
}
