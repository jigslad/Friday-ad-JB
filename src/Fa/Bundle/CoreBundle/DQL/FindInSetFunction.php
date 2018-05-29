<?php

namespace Fa\Bundle\CoreBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "FIND_IN_SET('2', Userdefined01)"
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class FindInSetFunction extends FunctionNode
{
    public $needle = null;
    public $haystack = null;
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->needle = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->haystack = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'FIND_IN_SET(' .
            $this->needle->dispatch($sqlWalker) . ', ' .
            $this->haystack->dispatch($sqlWalker) .
        ')';
    }
}
