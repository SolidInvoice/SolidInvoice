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

namespace SolidInvoice\ApiBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use SolidInvoice\ApiBundle\Action\Company\UploadCompanyLogo;
use SolidInvoice\ApiBundle\State\Processor\CompanyProfileProcessor;
use SolidInvoice\ApiBundle\State\Provider\CompanyProfileProvider;
use SolidInvoice\CoreBundle\Validator\Constraints\CompanyName;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The company's details as they appear on its invoices and quotes, the same as Settings → Company in
 * the web app.
 */
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/company', uriVariables: []),
        // A PATCH without URI variables is not read by default, and would start from a blank object.
        new Patch(uriTemplate: '/company', uriVariables: [], read: true, processor: CompanyProfileProcessor::class),
        new Post(
            uriTemplate: '/company/logo',
            inputFormats: ['multipart' => ['multipart/form-data']],
            uriVariables: [],
            status: 200,
            controller: UploadCompanyLogo::class,
            openapi: new Operation(
                summary: 'Uploads the company logo.',
                description: 'A PNG, JPEG or WebP image of at most 5 MB, as the `file` field of a multipart/form-data request. It is scaled down to at most 512 px and its EXIF data is removed.',
                requestBody: new RequestBody(content: new ArrayObject([
                    'multipart/form-data' => new MediaType(new ArrayObject([
                        'type' => 'object',
                        'properties' => ['file' => ['type' => 'string', 'format' => 'binary']],
                    ])),
                ])),
            ),
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            name: 'company_logo_upload',
        ),
        new Delete(uriTemplate: '/company/logo', uriVariables: [], read: true, name: 'company_logo_delete', processor: CompanyProfileProcessor::class),
    ],
    normalizationContext: ['groups' => ['company_profile:read'], 'skip_null_values' => false],
    denormalizationContext: ['groups' => ['company_profile:write']],
    graphQlOperations: [],
    provider: CompanyProfileProvider::class,
)]
final class CompanyProfile
{
    #[Groups(['company_profile:read', 'company_profile:write'])]
    #[CompanyName]
    public string $name = '';

    #[Groups(['company_profile:read', 'company_profile:write'])]
    #[Assert\Email]
    public ?string $email = null;

    #[Groups(['company_profile:read', 'company_profile:write'])]
    #[Assert\Length(max: 50)]
    public ?string $phone = null;

    /**
     * @var array{street1?: string|null, street2?: string|null, city?: string|null, state?: string|null, zip?: string|null, country?: string|null}|null
     */
    #[Groups(['company_profile:read', 'company_profile:write'])]
    #[ApiProperty(schema: [
        'type' => ['object', 'null'],
        'properties' => [
            'street1' => ['type' => 'string'],
            'street2' => ['type' => 'string'],
            'city' => ['type' => 'string'],
            'state' => ['type' => 'string'],
            'zip' => ['type' => 'string'],
            'country' => ['type' => 'string', 'description' => 'ISO 3166-1 alpha-2'],
        ],
    ])]
    #[Assert\Collection(
        fields: [
            'street1' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
            'street2' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
            'city' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
            'state' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
            'zip' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
            'country' => new Assert\Optional([new Assert\Country()]),
        ],
    )]
    public ?array $address = null;

    /**
     * ISO 3166-1 alpha-2.
     */
    #[Groups(['company_profile:read', 'company_profile:write'])]
    #[Assert\Country]
    public ?string $country = null;

    /**
     * ISO 4217. Invoices and quotes already issued keep the currency they were issued in.
     */
    #[Groups(['company_profile:read', 'company_profile:write'])]
    #[Assert\NotBlank]
    #[Assert\Currency]
    public ?string $currency = null;

    #[Groups(['company_profile:read'])]
    #[ApiProperty(writable: false)]
    public ?string $logoUrl = null;

    /**
     * The company's tax and registration numbers. Managed in Settings → Company in the web app.
     *
     * @var list<array{label: string|null, value: string|null, primary: bool}>
     */
    #[Groups(['company_profile:read'])]
    #[ApiProperty(writable: false)]
    public array $taxIdentifiers = [];
}
