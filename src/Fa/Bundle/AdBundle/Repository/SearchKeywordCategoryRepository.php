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
class SearchKeywordCategoryRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'skc';

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
     * Remove by search keyword id.
     *
     * @param integer $keywordId Keyword id.
     */
    public function removeByKeywordId($keywordId)
    {
        $searchKeywordCategories = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.search_keyword_id = :search_keyword_id')
        ->setParameter('search_keyword_id', $keywordId)
        ->getQuery()
        ->getResult();

        if ($searchKeywordCategories) {
            foreach ($searchKeywordCategories as $searchKeywordCategory) {
                $this->_em->remove($searchKeywordCategory);
            }

            $this->_em->flush();
        }
    }

    /**
     * Get category ids.
     *
     * @param array $keywordId Keyword id array.
     *
     * @return array
     */
    public function getCategoryIdsByKeywordId($keywordId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
                   ->select(self::ALIAS.'.search_keyword_id', self::ALIAS.'.category_id')
                   ->andWhere(self::ALIAS.'.category_id IS NOT NULL');

        if (!is_array($keywordId)) {
            $keywordId = array($keywordId);
        }

        if (count($keywordId)) {
            $qb->andWhere(self::ALIAS.'.search_keyword_id IN (:keywordId)');
            $qb->setParameter('keywordId', $keywordId);
        }

        $objResources = $qb->getQuery()->getArrayResult();

        $arr = array();
        if (count($objResources)) {
            foreach ($objResources as $objResource) {
                $arr[$objResource['search_keyword_id']][] = $objResource['category_id'];
            }
        }

        return $arr;
    }

    /**
     * Get keywords based on search term.
     *
     * @param string $term Term.
     *
     * @return array
     */
    public function getKeywordsArrayByText($term)
    {
        $keywords = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.keyword LIKE :term')
        ->setParameter('term', $term.'%')
        ->orderBy(self::ALIAS.'.search_count', 'desc')
        ->addOrderBy(self::ALIAS.'.id', 'asc')
        ->setMaxResults(3)
        ->getQuery()->getResult();

        $keywordsArray = array();
        $keywordIds    = array();
        $position      = 1;
        foreach ($keywords as $keyword) {
            $keywordIds[]    = $keyword->getId();
            $keywordCategory = explode(' in ', $keyword->getKeyword());
            $keywordText     = '<b>';
            for ($i = 0; $i <= (count($keywordCategory) -2); $i++) {
                $keywordText .= $keywordCategory[$i].' in ';
            }
            $keywordText = trim($keywordText, ' in ');
            $keywordText .= '</b>';

            if (isset($keywordCategory[count($keywordCategory) -1]) && $keywordCategory[count($keywordCategory) -1]) {
                $keywordText .= ' in '.$keywordCategory[count($keywordCategory) -1];
            }

            $keywordsArray[] = array('id'=> ($keywordCategory[0].'--'.$keyword->getCategoryId()), 'position' => $position, 'text' => $keywordText);
            $position++;
        }

        // Skip above 3 suggestions and find another three suggestion without category.
        if (count($keywordIds)) {
            $keywords = $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.keyword LIKE :term')
            ->setParameter('term', $term.'%')
            ->andWhere(self::ALIAS.'.category_id IS NULL')
            ->orderBy(self::ALIAS.'.search_count', 'desc')
            ->addOrderBy(self::ALIAS.'.id', 'asc')
            ->setMaxResults(3)
            ->andWhere(self::ALIAS.'.id NOT IN (:keywordIds)')
            ->setParameter('keywordIds', $keywordIds)
            ->getQuery()->getResult();

            foreach ($keywords as $keyword) {
                $keywordsArray[] = array('id'=> ($keyword->getKeyword().'--'), 'position' => $position, 'text' => '<b>'.$keyword->getKeyword().'</b>');
                $position++;
            }
        }

        return $keywordsArray;
    }
}
