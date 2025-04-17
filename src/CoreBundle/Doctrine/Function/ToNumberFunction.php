<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Doctrine\Function;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class ToNumberFunction extends FunctionNode
{
    private Node $field;

    /**
     * @throws ASTException
     * @throws Exception
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        if ($sqlWalker->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return 'TO_NUMBER(' . $this->field->dispatch($sqlWalker) . ', \'999999999999.99\')';
        }

        return $this->field->dispatch($sqlWalker);
    }

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->field = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
