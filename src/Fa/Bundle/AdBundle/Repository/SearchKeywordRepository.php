<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Shortlist repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version 1.0
 */
class SearchKeywordRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'sk';

    /**
     * PrepareQueryBuilder.
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
     * Add keyword filter to existing query object.
     *
     * @param mixed $keyword Keyword.
     */
    protected function addKeywordFilter($keyword = null)
    {
        if ($keyword) {
            if (!is_array($keyword)) {
                $keyword = array($keyword);
            }

            $keyword = array_filter($keyword);

            if (count($keyword)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.keyword IN (:'.$this->getRepositoryAlias().'_keyword'.')');
                $this->queryBuilder->setParameter($this->getRepositoryAlias().'_keyword', $keyword);
            }
        }
    }

    /**
     * Add keyword partial text filter to existing query object.
     *
     * @param string $keyword Keyword.
     */
    protected function addKeywordTextFilter($keyword = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.keyword LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $keyword));
    }
}
