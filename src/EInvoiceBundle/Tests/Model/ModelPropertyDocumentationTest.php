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

namespace SolidInvoice\EInvoiceBundle\Tests\Model;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function array_map;
use function basename;
use function glob;
use function preg_match;
use function sprintf;

/**
 * The acceptance criterion "each DTO property docblock names its BT/BG code" only holds as the
 * model grows if it is checked mechanically. This scans every class under `Model/` and asserts
 * every promoted property's docblock names at least one business term.
 */
final class ModelPropertyDocumentationTest extends TestCase
{
    private const string MODEL_ROOT = __DIR__ . '/../../Model';

    private const string MODEL_NAMESPACE = 'SolidInvoice\\EInvoiceBundle\\Model\\';

    public function testEveryPromotedPropertyDocumentsABusinessTerm(): void
    {
        $files = glob(self::MODEL_ROOT . '/*.php');
        self::assertIsArray($files);
        self::assertNotEmpty($files, sprintf('Expected to find at least one class under "%s".', self::MODEL_ROOT));

        $classes = array_map(
            static fn (string $file): string => self::MODEL_NAMESPACE . basename($file, '.php'),
            $files
        );

        $violations = [];

        foreach ($classes as $class) {
            self::assertTrue(class_exists($class), sprintf('%s does not resolve to a class.', $class));

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getProperties() as $property) {
                $docComment = $property->getDocComment();

                if ($docComment === false || preg_match('/BT-\d+|BG-\d+/', $docComment) !== 1) {
                    $violations[] = sprintf('%s::$%s has no BT/BG code in its docblock', $class, $property->getName());
                }
            }
        }

        self::assertSame([], $violations, "Undocumented Model properties found:\n" . implode("\n", $violations));
    }
}
