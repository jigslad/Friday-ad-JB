<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Walker;

use Doctrine\ORM\Query\SqlWalker;

/**
 * This is sql walker which is used to modify sql query for native database support syntax
 * which is not supported by doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAdminSearchSqlWalker extends SqlWalker
{
    /**
     * Walks down a walkFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param $fromClause Form clause.
     *
     * @return string The SQL.
     */
    public function walkFromClause($fromClause)
    {
        $sql = parent::walkFromClause($fromClause);

        if ($this->getQuery()->getHint('userAdminSearchSqlWalker.userIndex') === true) {
            $sql = preg_replace("/(FROM user u\d)_(\s)?/", "$1_ USE INDEX (fa_user_user_first_name_index, fa_user_user_last_name_index, fa_user_user_email_index, fa_user_user_phone_index, fa_user_user_paypal_email_index) ", $sql);
        }

        if ($this->getQuery()->getHint('userAdminSearchSqlWalker.adIndex') === true) {
            $sql = preg_replace("/(LEFT JOIN ad a\d)_\s/", "$1_ USE INDEX (fa_ad_ad_created_at_index, fa_ad_ad_expires_at_index) ", $sql);
        }

        if ($this->getQuery()->getHint('userAdminSearchSqlWalker.userStatisticsIndex') === true) {
            $sql = preg_replace("/(LEFT JOIN user_statistics ust\d)_\s/", "$1_ USE INDEX (fa_user_user_statistics_total_ad_index, fa_user_user_statistics_total_active_ad_index) ", $sql);
        }

        return $sql;
    }
}
