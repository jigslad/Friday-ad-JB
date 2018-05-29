<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * "DATE_FORMAT".
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DateFormatFunction extends FunctionNode
{

    /**
     * Holds the timestamp of the DATE_FORMAT DQL statement.
     *
     * @var $dateExpression
     */
    protected $dateExpression;

    /**
     * Holds the '% format' parameter of the DATE_FORMAT DQL statement.
     *
     * var String
     */
    protected $formatChar;

    /**
     * GetSql.
     *
     * @param SqlWalker $sqlWalker
     *
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::getSql()
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'DATE_FORMAT (FROM_UNIXTIME('.$sqlWalker->walkArithmeticExpression($this->dateExpression).'),'.$sqlWalker->walkStringPrimary($this->formatChar).')';
    }

    /**
     * Parse.
     *
     * @param Parser $parser
     *
     * @see \Doctrine\ORM\Query\AST\Functions\FunctionNode::parse()
     */
    public function parse(Parser $parser)
    {
        $parser->Match(Lexer::T_IDENTIFIER);
        $parser->Match(Lexer::T_OPEN_PARENTHESIS);

        $this->dateExpression = $parser->ArithmeticExpression();
        $parser->Match(Lexer::T_COMMA);

        $this->formatChar = $parser->ArithmeticExpression();

        $parser->Match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
