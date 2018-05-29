<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\SimilarAdApi;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\SimilarAdApi\SimilarAdFieldMappingInterface;
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
class SimilarAdResponseBuild
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
        $imageThumbUrl      = null;

        if (isset($adSolrObj[AdSolrFieldMapping::ORD]) && isset($adSolrObj[AdSolrFieldMapping::HASH]) && isset($adSolrObj[AdSolrFieldMapping::PATH])) {
            $imageThumbUrl = CommonManager::getAdImageUrl($this->container, $adSolrObj[AdSolrFieldMapping::ID], $adSolrObj[AdSolrFieldMapping::PATH][0], $adSolrObj[AdSolrFieldMapping::HASH][0], '300X225', (isset($adSolrObj[AdSolrFieldMapping::AWS]) ? $adSolrObj[AdSolrFieldMapping::AWS][0] : 0), (isset($adSolrObj[AdSolrFieldMapping::IMAGE_NAME])) ? $adSolrObj[AdSolrFieldMapping::IMAGE_NAME][0] : null);
        }

        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::TITLE] = (isset($adSolrObj[AdSolrFieldMapping::TITLE]) ? $adSolrObj[AdSolrFieldMapping::TITLE] : null);

        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::AD_URL] = $adUrl;

        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::DESCRIPTION] = (isset($adSolrObj[AdSolrFieldMapping::DESCRIPTION]) ? $adSolrObj[AdSolrFieldMapping::DESCRIPTION] : null);

        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::IMAGE_THUMB_URL] = $imageThumbUrl;

        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::NUMBER_OF_IMAGES] = (isset($adSolrObj[AdSolrFieldMapping::ORD]) ? count($adSolrObj[AdSolrFieldMapping::ORD]) : 0);

        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::PRICE] = (isset($adSolrObj[AdSolrFieldMapping::PRICE]) ? $adSolrObj[AdSolrFieldMapping::PRICE] : null);

        $townId = (isset($adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID]) ? $adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID] : null);
        $this->similarAdApiResponse[SimilarAdFieldMappingInterface::TOWN] = ($townId ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $townId) : null);
    }
}
