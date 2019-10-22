<?php


namespace Fa\Bundle\ContentBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Native Banner Ad page repository.
 *
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class NativeBannerAdRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;
    const ALIAS = 'bna';

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