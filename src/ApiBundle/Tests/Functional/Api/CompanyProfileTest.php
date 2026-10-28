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

namespace SolidInvoice\ApiBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiResource\CompanyProfile;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function file_put_contents;
use function getimagesizefromstring;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefill;
use function imagepng;
use function sys_get_temp_dir;
use function tempnam;

#[Group('functional')]
final class CompanyProfileTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return CompanyProfile::class;
    }

    public function testTheProfileCanBeReadAndChanged(): void
    {
        $profile = $this->requestGet('/api/company');
        self::assertSame($this->company->getName(), $profile['name']);
        self::assertNull($profile['logoUrl']);
        self::assertSame([], $profile['taxIdentifiers']);

        $profile = $this->requestPatch('/api/company', [
            'name' => 'Acme Plumbing',
            'email' => 'jobs@acme.test',
            'phone' => '+27 21 555 0100',
            'address' => ['street1' => '12 Main Rd', 'city' => 'Cape Town', 'country' => 'ZA'],
            'country' => 'ZA',
            'currency' => 'ZAR',
        ]);

        self::assertSame('Acme Plumbing', $profile['name']);
        self::assertSame('jobs@acme.test', $profile['email']);
        self::assertSame(['street1' => '12 Main Rd', 'city' => 'Cape Town', 'country' => 'ZA'], $profile['address']);
        self::assertSame('ZAR', $profile['currency']);

        $company = self::getContainer()->get('doctrine')->getManager()->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        self::assertSame('Acme Plumbing', $company->getName());
        self::assertSame('ZA', $company->getCountry());
    }

    public function testInvalidValuesAreRejected(): void
    {
        self::$client->request(Request::METHOD_PATCH, '/api/company', [
            'json' => ['email' => 'nope', 'country' => 'XX', 'currency' => 'ABC', 'address' => ['planet' => 'Mars']],
            'headers' => ['content-type' => 'application/merge-patch+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'email'],
            ['propertyPath' => 'address[planet]'],
            ['propertyPath' => 'country'],
            ['propertyPath' => 'currency'],
        ]]);
    }

    public function testTheLogoIsScaledDownServedAndRemoved(): void
    {
        $image = imagecreatetruecolor(1200, 600);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 46, 150, 58));
        $path = (string) tempnam(sys_get_temp_dir(), 'logo');
        imagepng($image, $path);

        self::$client->request(Request::METHOD_POST, '/api/company/logo', [
            'headers' => ['content-type' => 'multipart/form-data', 'accept' => 'application/ld+json'],
            'extra' => ['files' => ['file' => new UploadedFile($path, 'logo.png', 'image/png', test: true)]],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['logoUrl' => 'http://localhost/api/company/logo']);

        $response = self::$client->request(Request::METHOD_GET, '/api/company/logo');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'image/png');
        $size = getimagesizefromstring($response->getContent());
        self::assertNotFalse($size);
        self::assertSame([512, 256], [$size[0], $size[1]]);

        self::$client->request(Request::METHOD_DELETE, '/api/company/logo');
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        self::$client->request(Request::METHOD_GET, '/api/company/logo');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testANonImageIsRejected(): void
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg"/>');

        self::$client->request(Request::METHOD_POST, '/api/company/logo', [
            'headers' => ['content-type' => 'multipart/form-data', 'accept' => 'application/ld+json'],
            'extra' => ['files' => ['file' => new UploadedFile($path, 'logo.svg', 'image/svg+xml', test: true)]],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['propertyPath' => 'file']]]);
    }
}
