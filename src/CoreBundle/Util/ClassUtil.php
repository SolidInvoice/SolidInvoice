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

use function defined;

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
        $tokens = token_get_all(file_get_contents($file));
        $count = count($tokens);

        foreach ($tokens as $i => $token) {
            if (! is_array($token)) {
                continue;
            }

            if ($class && T_STRING === $token[0]) {
                return $namespace . '\\' . $token[1];
            }

            if (true === $namespace && ((defined('T_NAME_QUALIFIED') && T_NAME_QUALIFIED === $token[0]) || T_STRING === $token[0])) {
                if (defined('T_NAME_QUALIFIED') && T_NAME_QUALIFIED === $token[0]) {
                    $namespace = $token[1];
                } else {
                    $namespace = '';
                    do {
                        $namespace .= $token[1];
                        $token = $tokens[++$i];
                    } while ($i < $count && is_array($token) && in_array($token[0], [T_NS_SEPARATOR, T_STRING], true));
                }
            }

            if (T_CLASS === $token[0]) {
                $class = true;
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return null;
    }
}
