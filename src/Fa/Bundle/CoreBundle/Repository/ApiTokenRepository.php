<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ApiTokenRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'at';

    const PRINT_API_TYPE_ID      = 1;
    const SIMILAR_AD_API_TYPE_ID = 2;
    const AD_API_TYPE_ID = 3;

    /**
     * prepareQueryBuilder.
     *
     * @param array $data array of data
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Add entity type filter to existing query object
     *
     * @param integer $type entity type
     *
     * @return void
     */
    protected function addTypeFilter($type = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.type = '.$type);
    }
}
