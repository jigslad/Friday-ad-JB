<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Ad feed mapping repository.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdFeedMappingRepository extends EntityRepository
{

    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'afm';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Add text filter to existing query object.
     *
     * @param string $text Text
     */
    protected function addTextFilter($title = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.text LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $title));
    }

    /**
     * Add target filter to existing query object.
     *
     * @param string $target Target
     */
    protected function addTargetFilter($target = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.target LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $target));
    }

    /**
     * Get table name.
     */
    private function getTableName()
    {
        return $this->_em->getClassMetadata('AdFeedBundle:AdFeedMapping')->getTableName();
    }
}
