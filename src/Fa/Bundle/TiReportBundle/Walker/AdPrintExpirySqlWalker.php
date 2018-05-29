<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Walker;

use Doctrine\ORM\Query\SqlWalker;
use Fa\Bundle\TiReportBundle\Repository\AdPrintReportDailyRepository;

/**
 * This is sql walker.
 *
 * This walker is used to modify the sql generated by doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdPrintExpirySqlWalker extends SqlWalker
{
    /**
     * Walks down a walkSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param $selectClause
     *
     * @return string The SQL.
     */
    public function walkSelectClause($selectClause)
    {
        $sql = parent::walkSelectClause($selectClause);
        $tablealias = $this->getSQLTableAlias($this->getEntityManager()->getClassMetadata('FaTiReportBundle:AdPrintReportDaily')->getTableName(), AdPrintReportDailyRepository::ALIAS);

        if ($this->getQuery()->getHint('adPrintReport.count') === true) {
            $sql = str_replace("count(DISTINCT ".$tablealias.".id)", "count(DISTINCT ".$tablealias.".ad_id)", $sql);
        }

        return $sql;
    }
}
