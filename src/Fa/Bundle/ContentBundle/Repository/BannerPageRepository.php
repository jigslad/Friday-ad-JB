<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Banner page repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Sagar Lotiya <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BannerPageRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'bp';

    const PAGE_HOME               = 1;
    const PAGE_SEARCH_RESULTS     = 2;
    const PAGE_AD_DETAILS         = 3;
    const PAGE_ALL_OTHER          = 4;
    const PAGE_LANDING_PAGE       = 5;

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }
}
