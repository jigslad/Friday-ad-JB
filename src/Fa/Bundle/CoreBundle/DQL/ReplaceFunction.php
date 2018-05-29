<?php

namespace Fa\Bundle\CoreBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "REPLACE" "(" StringPrimary "," StringSecondary "," StringThird ")"
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ReplaceFunction extends FunctionNode
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
     * Third string.
     *
     * @var $stringThird
     */
    public $stringThird;

    /**
     * GetSql.
     *
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::getSql()
     *
     * @return string
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return  'replace('.$this->stringFirst->dispatch($sqlWalker) .','
                . $this->stringSecond->dispatch($sqlWalker) . ','
                .$this->stringThird->dispatch($sqlWalker) . ')';
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
        $parser->match(Lexer::T_COMMA);
        $this->stringThird = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
