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

namespace SolidInvoice\ProductBundle\Tests\Form\Type;

use const JSON_THROW_ON_ERROR;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function json_decode;

#[Group('functional')]
final class ProductAutocompleteTypeTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testReturnsOnlyTheCurrentCompanysMatches(): void
    {
        ProductFactory::createOne(['name' => 'Website design', 'price' => BigDecimal::of('4500'), 'company' => $this->company]);
        ProductFactory::createOne(['name' => 'Logo', 'company' => $this->company]);
        ProductFactory::createOne(['name' => 'Website hosting', 'company' => CompanyFactory::createOne(['name' => 'Other Inc'])]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $client = $this->client();
        $client->request('GET', '/autocomplete/product_autocomplete_type?query=web');

        self::assertResponseIsSuccessful();
        $results = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['results'];

        self::assertCount(1, $results);
        self::assertStringContainsString('Website design', (string) $results[0]['text']);
        self::assertStringContainsString('4500', (string) $results[0]['text']);
    }

    public function testIsForbiddenWhenTheFeatureIsOff(): void
    {
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn(false);

        $client = $this->client($gate);
        $client->request('GET', '/autocomplete/product_autocomplete_type?query=web');

        self::assertResponseStatusCodeSame(403);
    }

    private function client(?FeatureGate $gate = null): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        if ($gate instanceof FeatureGate) {
            self::getContainer()->set(FeatureGate::class, $gate);
        }

        $client->loginUser(UserFactory::createOne(['companies' => [$this->company]]));

        return $client;
    }
}
