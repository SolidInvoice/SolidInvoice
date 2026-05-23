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

namespace SolidInvoice\CoreBundle\Tests\Form\Type;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;
use Symfony\UX\Dropzone\Form\DropzoneType;

final class ImageUploadTypeTest extends TestCase
{
    private FormFactoryInterface $factory;

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $validator = Validation::createValidator();

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions([
                new PreloadedExtension(
                    [
                        new ImageUploadType($validator),
                        new DropzoneType(),
                    ],
                    [],
                ),
                new HttpFoundationExtension(),
            ])
            ->getFormFactory();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testSubmitValidPngImageIsTransformedToEncodedValue(): void
    {
        $form = $this->factory->create(ImageUploadType::class);

        $form->submit($this->createPngUpload('logo.png'));

        self::assertTrue($form->isSynchronized(), (string) $form->getTransformationFailure()?->getMessage());

        $data = $form->getData();
        self::assertIsString($data);
        self::assertStringContainsString('|', $data);

        [$extension, $encoded] = explode('|', $data, 2);
        self::assertSame('png', $extension);
        self::assertNotEmpty(base64_decode($encoded, true));
    }

    public function testSubmitValidJpegImageIsTransformedToEncodedValue(): void
    {
        $form = $this->factory->create(ImageUploadType::class);

        $form->submit($this->createJpegUpload('logo.jpg'));

        self::assertTrue($form->isSynchronized(), (string) $form->getTransformationFailure()?->getMessage());

        [$extension] = explode('|', (string) $form->getData(), 2);
        self::assertSame('jpg', $extension);
    }

    public function testSubmitSvgWithEmbeddedScriptIsRejected(): void
    {
        $svg = <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">
              <script>alert(document.cookie)</script>
            </svg>
            SVG;

        $form = $this->factory->create(ImageUploadType::class);
        $form->submit($this->createUploadedFile('logo.svg', $svg, 'image/svg+xml'));

        self::assertFalse(
            $form->isSynchronized(),
            'SVG uploads must be rejected to prevent stored XSS via embedded scripts.',
        );
    }

    public function testSubmitTextFileDisguisedAsImageIsRejected(): void
    {
        $form = $this->factory->create(ImageUploadType::class);
        $form->submit($this->createUploadedFile('logo.png', 'not an image at all', 'image/png'));

        self::assertFalse(
            $form->isSynchronized(),
            'Files that do not contain actual image data must be rejected.',
        );
    }

    public function testSubmitPhpFileIsRejected(): void
    {
        $form = $this->factory->create(ImageUploadType::class);
        $form->submit($this->createUploadedFile('logo.php', "<?php echo 'pwned'; ?>", 'application/x-php'));

        self::assertFalse($form->isSynchronized());
    }

    public function testSubmittingNullReturnsNull(): void
    {
        $form = $this->factory->create(ImageUploadType::class);
        $form->submit(null);

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    private function createPngUpload(string $name): UploadedFile
    {
        $image = imagecreatetruecolor(2, 2);
        self::assertNotFalse($image);

        $path = $this->tempFile('png');
        imagepng($image, $path);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function createJpegUpload(string $name): UploadedFile
    {
        $image = imagecreatetruecolor(2, 2);
        self::assertNotFalse($image);

        $path = $this->tempFile('jpg');
        imagejpeg($image, $path);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    private function createUploadedFile(string $name, string $contents, string $mimeType): UploadedFile
    {
        $path = $this->tempFile('bin');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function tempFile(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'image_upload_test_') . '.' . $extension;
        $this->tempFiles[] = $path;

        return $path;
    }
}
