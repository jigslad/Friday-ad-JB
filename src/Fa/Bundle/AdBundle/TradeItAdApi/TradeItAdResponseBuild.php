<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\TradeItAdApi;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\TradeItAdApi\TradeItAdFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This controller is used for similar ad api.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class TradeItAdResponseBuild
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Similar ad api response.
     *
     * @var array
     */

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Init.
     *
     * @param object $adSolrObj Solr object
     *
     * @return mixed
     */
    public function init($adSolrObj)
    {
        $this->buildBasicFieldArray($adSolrObj);

        return $this->similarAdApiResponse;
    }

    /**
     * Build basic field array.
     *
     * @param object $adSolrObj Solr object
     */
    protected function buildBasicFieldArray($adSolrObj)
    {
        $adUrl              = $this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($adSolrObj);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $this->em           = $this->container->get('doctrine')->getManager();
        $imageThumbUrl      = null;

        if (isset($adSolrObj[AdSolrFieldMapping::ORD]) && isset($adSolrObj[AdSolrFieldMapping::HASH]) && isset($adSolrObj[AdSolrFieldMapping::PATH])) {
            $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $adSolrObj[AdSolrFieldMapping::ID], $adSolrObj[AdSolrFieldMapping::PATH][0], $adSolrObj[AdSolrFieldMapping::HASH][0], '', (isset($adSolrObj[AdSolrFieldMapping::AWS]) ? $adSolrObj[AdSolrFieldMapping::AWS][0] : 0), (isset($adSolrObj[AdSolrFieldMapping::IMAGE_NAME])) ? $adSolrObj[AdSolrFieldMapping::IMAGE_NAME][0] : null);
        }

        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::TITLE] = (isset($adSolrObj[AdSolrFieldMapping::TITLE]) ? $adSolrObj[AdSolrFieldMapping::TITLE] : null);

        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::ID]      = $adSolrObj[AdSolrFieldMapping::ID];
        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::AD_URL]  = $adUrl;
        $this->similarAdApiResponse['ad_type_id'] = (isset($adSolrObj[AdSolrFieldMapping::TYPE_ID]) ? $adSolrObj[AdSolrFieldMapping::TYPE_ID] : null);
        ;

        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::DESCRIPTION] = (isset($adSolrObj[AdSolrFieldMapping::DESCRIPTION]) ? $adSolrObj[AdSolrFieldMapping::DESCRIPTION] : null);

        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::IMAGE_THUMB_URL] = $imageThumbUrl;

        $this->similarAdApiResponse['published_date'] = (isset($adSolrObj[AdSolrFieldMapping::PUBLISHED_AT]) ? $adSolrObj[AdSolrFieldMapping::PUBLISHED_AT] : null);
        $this->similarAdApiResponse['updated_date']    = (isset($adSolrObj[AdSolrFieldMapping::UPDATED_AT]) ? $adSolrObj[AdSolrFieldMapping::UPDATED_AT] : null);
        $this->similarAdApiResponse['expired_date']    = (isset($adSolrObj[AdSolrFieldMapping::EXPIRES_AT]) ? $adSolrObj[AdSolrFieldMapping::EXPIRES_AT] : null);
        $this->similarAdApiResponse['status']          = (isset($adSolrObj[AdSolrFieldMapping::STATUS_ID]) ? $adSolrObj[AdSolrFieldMapping::STATUS_ID] : null);
        $parent   = $this->getFirstLevelParent($adSolrObj[AdSolrFieldMapping::CATEGORY_ID]);
        $this->similarAdApiResponse['parent_category'] = $parent['name'];
        $this->similarAdApiResponse['delivery_method_option_id'] = (isset($adSolrObj[AdSolrFieldMapping::DELIVERY_METHOD_OPTION_ID]) ? $adSolrObj[AdSolrFieldMapping::DELIVERY_METHOD_OPTION_ID] : null);
        ;

        $slug = $this->em->getRepository('FaEntityBundle:Category')->getFullSlugById($adSolrObj[AdSolrFieldMapping::CATEGORY_ID], $this->container);
        $this->similarAdApiResponse['category_slug'] = $slug;
        $this->similarAdApiResponse['category'] = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $adSolrObj[AdSolrFieldMapping::CATEGORY_ID]);


        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::NUMBER_OF_IMAGES] = (isset($adSolrObj[AdSolrFieldMapping::ORD]) ? count($adSolrObj[AdSolrFieldMapping::ORD]) : 0);

        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::PRICE] = (isset($adSolrObj[AdSolrFieldMapping::PRICE]) ? $adSolrObj[AdSolrFieldMapping::PRICE] : null);

        $townId = (isset($adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID]) ? $adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID] : null);

        $adListFields =  $this->em->getRepository('FaAdBundle:Ad')->getAdListingFields($adSolrObj[AdSolrFieldMapping::CATEGORY_ID], $adSolrObj, $this->container);

        foreach ($adListFields as $key => $val) {
            if ($key == 'contract_type_id' && is_array($val)) {
                $val = implode(',', $val);
            }

            $this->similarAdApiResponse[$key] = $val;
        }

        $root                          = $this->em->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($adSolrObj[AdSolrFieldMapping::CATEGORY_ID]);
        $repository                    = $this->em->getRepository('FaAdBundle:'.'Ad'.str_replace(' ', '', $root->getName()));
        $this->similarAdApiResponse['dimensions'] = $repository->findByAdId($adSolrObj[AdSolrFieldMapping::ID]);

        $this->similarAdApiResponse[TradeItAdFieldMappingInterface::TOWN] = ($townId ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $townId) : null);
    }

    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getFirstLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->container);
        return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById(key($cat), $this->container);
    }
}
