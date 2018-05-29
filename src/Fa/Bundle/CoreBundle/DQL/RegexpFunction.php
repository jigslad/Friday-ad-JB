<?php

namespace Fa\Bundle\CoreBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "REGEXP" "(" StringPrimary "," StringSecondary ")"
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class RegexpFunction extends FunctionNode
{
    /**
     * First string.
     *
     * @var $stringFirst
     */
    public $stringFirst;

    /**
     * Second string.
     *
     * @var $stringSecond
     */
    public $stringSecond;

    /**
     * GetSql.
     *
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::getSql()
     *
     * @return string
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return $this->stringFirst->dispatch($sqlWalker) . ' REGEXP ' . $this->stringSecond->dispatch($sqlWalker) ;
    }

    /**
     * Parse.
     *
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::parse()
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->stringFirst = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->stringSecond = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
