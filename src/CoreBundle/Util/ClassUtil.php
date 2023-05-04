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

namespace SolidInvoice\CoreBundle\Util;

use PhpToken;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Util\ClassUtilTest
 */
class ClassUtil
{
    /**
     * Taken from https://github.com/FriendsOfSymfony/FOSRestBundle/blob/e7d987b310ec77376a85eabdc84a8df98892dd09/Routing/Loader/ClassUtils.php.
     */
    public static function findClassInFile(string $file): ?string
    {
        $class = false;
        $namespace = false;
        $tokens = PhpToken::tokenize(file_get_contents($file));

        foreach ($tokens as $token) {
            if ($class && $token->is(T_STRING)) {
                return $namespace . '\\' . $token->text;
            }

            if (true === $namespace && $token->is(T_NAME_QUALIFIED)) {
                $namespace = $token->text;
            }

            if ($token->is(T_CLASS)) {
                $class = true;
            }

            if ($token->is(T_NAMESPACE)) {
                $namespace = true;
            }
        }

        return null;
    }
}
