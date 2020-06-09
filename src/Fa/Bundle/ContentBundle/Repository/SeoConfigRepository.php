<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;

/**
 * Seo Config repository.
 *
 *
 * @author Nikhil Baby <nikhil.baby2000@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class SeoConfigRepository extends EntityRepository
{
    const ALIAS = 'sc';

    const UNNECESSARY_QUERY_PARAMS = 'unnecessary_query_params';

    const UNNECESSARY_ODATA_PARAMS = 'unnecessary_odata_params';

    const CATEGORY_ALIAS = 'category_alias';

    const FILTER_ALIAS = 'filter_alias';

    const REGION_ALIAS = 'region_alias';

    const LOCATION_ALIAS = 'location_alias';

    const ALL_CATEGORY_ALIAS = 'all_category_alias';

    const FOR_SALE_EXCLUSION = 'for_sale_exclusion';

    const META_ROBOTS = 'meta_robots';

    const MAX_DIM_RULES = 'max_dim_rules';

    const REDIRECTS = 'redirects';

    const SUB_CATEGORY_CONFIG = 'sub_category_config';

    const KEYWORD_SEARCH_CONFIG = 'keyword_search_config';

    const CRAWL_CONFIG = 'crawl_config';

    const LHS_FILTER_ORDER = 'lhs_filter_order';

    const URL_RIGHT_TRIM = 'url_right_trim';

    /**
     * Prepare query builder.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * get Max Dim Rules For Generating url
     * @return object|null
     */
    public function getMaxDimRules(){
        return $this->findOneBy([
            'type' => self::MAX_DIM_RULES
        ]);
    }

    /**
     * get o data for removing from url
     *
     * @return object|null
     */
    public function getOdata(){
        return $this->findOneBy([
            'type' => self::UNNECESSARY_ODATA_PARAMS
        ]);
    }

    /**
     * get meta tag for roboots
     *
     * @param $url
     * @return object|null
     */
    public function getMetaRobots($url){
        $metaRobots = $this->findOneBy([
            'type' => self::META_ROBOTS
        ]);
        if($metaRobots){
            if($metaRobots->getStatus()){
                $metaDatas = $metaRobots->getData();
                foreach ($metaDatas as $metaData){
                    $metaData = explode(':',$metaData);
                    if(strpos($url, $metaData[1]) !== false){
                        return $metaData[0];

                    }
                }
            }
        }
        return null;
    }
}
