<?php

namespace Fa\Bundle\CoreBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "GROUP_CONCAT" "(" String "," Seperator ")"
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class GroupConcatFunction extends FunctionNode
{
    /**
     * First string.
     *
     * @var $string
     */
    public $string;

    /**
     * Third string.
     *
     * @var $stringThird
     */
    public $seperator;

    /**
     * GetSql.
     *
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::getSql()
     *
     * @return string
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return  'group_concat('.$this->string->dispatch($sqlWalker).' SEPARATOR '.$this->seperator->dispatch($sqlWalker).')';
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
        $this->string = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->seperator = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
