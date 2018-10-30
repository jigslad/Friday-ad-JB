<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\AdApi;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\AdApi\AdFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This controller is used for Advert api.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdApiResponseBuild
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Ad api response.
     *
     * @var array
     */
    private $adApiResponse = array();

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
     * @param Ad    $ad
     * @param array $adSolrObjs
     *
     * @return mixed
     */
    public function init(Ad $ad, $adSolrObjs)
    {
        $this->buildBasicFieldArray($ad, $adSolrObjs);

        $this->buildClassificationArray($ad);

        $this->buildImageArray($ad, $adSolrObjs);

        $this->buildAdditionalFieldArray($ad);

        $this->buildUserFieldArray($ad);

        return $this->adApiResponse;
    }

    /**
     * Build basic field array.
     *
     * @param Ad    $ad
     * @param array $adSolrObjs
     */
    protected function buildBasicFieldArray(Ad $ad, $adSolrObjs)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $adRoutingManager   = $this->container->get('fa_ad.manager.ad_routing');
        $em                 = $this->container->get('doctrine')->getManager();
        if (isset($adSolrObjs[$ad->getId()])) {
            $adSolrObj = $adSolrObjs[$ad->getId()];
            $townId = (isset($adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID]) ? $adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID] : null);
            $domicileId = (isset($adSolrObj[AdSolrFieldMapping::DOMICILE_ID]) ? $adSolrObj[AdSolrFieldMapping::DOMICILE_ID][0] : null);
            $postcode = (isset($adSolrObj[AdSolrFieldMapping::POSTCODE]) ? $adSolrObj[AdSolrFieldMapping::POSTCODE][0] : null);

            $adUrl = $adRoutingManager->getDetailUrl($adSolrObj);
            $this->adApiResponse[AdFieldMappingInterface::COUNTY] = ($domicileId ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $domicileId) : null);

            $this->adApiResponse[AdFieldMappingInterface::TOWN] = ($townId ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $townId) : null);

            $this->adApiResponse[AdFieldMappingInterface::POSTCODE] = $postcode;
        } else {
            $location = $em->getRepository('FaAdBundle:AdLocation')->getLatestLocation($ad->getId());
            $adUrl    = $adRoutingManager->getDetailUrl($ad, $ad->getId(), $ad->getTitle(), $ad->getCategory()->getId(), ($location->getLocationTown() ? $location->getLocationTown()->getId() : ($location->getLocationDomicile() ? $location->getLocationDomicile()->getId() : null)));

            $this->adApiResponse[AdFieldMappingInterface::COUNTY] = ($location->getLocationDomicile() ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $location->getLocationDomicile()->getId()) : null);

            $this->adApiResponse[AdFieldMappingInterface::TOWN] = ($location->getLocationTown() ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $location->getLocationTown()->getId()) : null);

            $this->adApiResponse[AdFieldMappingInterface::POSTCODE] = ($location->getPostcode() ? $location->getPostcode() : null);
        }

        $this->adApiResponse[AdFieldMappingInterface::ADREF] = ($ad->getId() ? $ad->getId() : null);

        $this->adApiResponse[AdFieldMappingInterface::ADVERTDETAILSURL] = $adUrl;

        $this->adApiResponse[AdFieldMappingInterface::DESCRIPTION] = ($ad->getDescription() ? strip_tags($ad->getDescription()) : null);

        $this->adApiResponse[AdFieldMappingInterface::TITLE] = ($ad->getTitle() ? $ad->getTitle() : null);

        $this->adApiResponse[AdFieldMappingInterface::PRICE] = ($ad->getPrice() ? $ad->getPrice() : null);

        $this->adApiResponse[AdFieldMappingInterface::CURRENCY] = CommonManager::getCurrencyCode($this->container);

        $this->adApiResponse[AdFieldMappingInterface::DATE_CREATED] = ($ad->getCreatedAt() ? $this->getDate($ad->getCreatedAt()) : null);

        $this->adApiResponse[AdFieldMappingInterface::DATE_MODIFIED] = ($ad->getUpdatedAt() ? $this->getDate($ad->getUpdatedAt()) : null);

        $upsellArray = array();
        $upsells     = $em->getRepository('FaAdBundle:AdUserPackageUpsell')->getLatestAdPackageUpsell($ad->getId());
        if (count($upsells)) {
            foreach ($upsells as $upsellId => $upsellName) {
                $upsellArray[] = array('ID' => $upsellId, 'Name' => $upsellName);
            }
        }
        $this->adApiResponse[AdFieldMappingInterface::UPSELLS] = $upsellArray;
    }

    /**
     * BuildUser field array.
     *
     * @param Ad $ad
     */
    protected function buildUserFieldArray(Ad $ad)
    {
        $this->adApiResponse[AdFieldMappingInterface::ADVERTISER_ID] = ($ad->getUser() ? $ad->getUser()->getId() : null);

        $this->adApiResponse[AdFieldMappingInterface::EMAIL] = ($ad->getUser() ? $ad->getUser()->getEmail() : null);

        $this->adApiResponse[AdFieldMappingInterface::PHONE_NUMBER] = ($ad->getBusinessPhone() ? $ad->getBusinessPhone() : ($ad->getPrivacyNumber() ? $ad->getPrivacyNumber() : ($ad->getUser() ? $ad->getUser()->getPhone() : null)));
    }

    /**
     * Build classification array.
     *
     * @param Ad $ad
     */
    protected function buildClassificationArray(Ad $ad)
    {
        $categoryId = $ad->getCategory()->getId();

        $categoryArray  = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
        $classification = array();

        $i = 0;
        foreach ($categoryArray as $id => $title) {
            $classification[$i]['id']    = $id;
            $classification[$i]['name'] = $title;
            $i++;
        }

        $this->adApiResponse[AdFieldMappingInterface::CATEGORY]       = $classification;
        //$this->adApiResponse[AdFieldMappingInterface::CLASSIFICATION] = $classification;
    }

    /**
     * Build image array.
     *
     * @param Ad    $ad
     * @param array $adSolrObjs
     */
    protected function buildImageArray(Ad $ad, $adSolrObjs)
    {
        $this->adApiResponse[AdFieldMappingInterface::IMAGES] = array();
        if (isset($adSolrObjs[$ad->getId()])) {
            if (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::ORD]) && count($adSolrObjs[$ad->getId()][AdSolrFieldMapping::ORD])) {
                foreach ($adSolrObjs[$ad->getId()][AdSolrFieldMapping::ORD] as $index => $val) {
                    $imageUrl = CommonManager::getAdImageUrl($this->container, $adSolrObjs[$ad->getId()][AdSolrFieldMapping::ID], $adSolrObjs[$ad->getId()][AdSolrFieldMapping::PATH][$index], $adSolrObjs[$ad->getId()][AdSolrFieldMapping::HASH][$index], '800X600', (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::AWS]) ? $adSolrObjs[$ad->getId()][AdSolrFieldMapping::AWS][$index] : 0), (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::IMAGE_NAME])) ? $adSolrObjs[$ad->getId()][AdSolrFieldMapping::IMAGE_NAME][$index] : null);
                    if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                        $imageUrl = str_replace('//', 'http://', $imageUrl);
                    }
                    $this->adApiResponse[AdFieldMappingInterface::IMAGES][] = $imageUrl;
                }
            }
        } else {
            $images = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId(), 1);

            if (count($images)) {
                foreach ($images as $image) {
                    $imageUrl = CommonManager::getAdImageUrl($this->container, $ad->getId(), $image->getPath(), $image->getHash(), '800X600', $image->getAws(), $image->getImageName());
                    if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                        $imageUrl = str_replace('//', 'http://', $imageUrl);
                    }
                    $this->adApiResponse[AdFieldMappingInterface::IMAGES][] = $imageUrl;
                }
            }
        }
    }

    /**
     * Build additional field array.
     *
     * @param Ad $ad
     */
    protected function buildAdditionalFieldArray(Ad $ad)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $categoryId     = $ad->getCategory()->getId();
        $typeId         = ($ad->getType() ? $ad->getType()->getId() : null);
        $object         = null;
        $rootCategoryId = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        $className      = CommonManager::getCategoryClassNameById($rootCategoryId, true);
        $repository     = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:'.'Ad'.$className);
        $object         = $repository->findOneBy(array('ad' => $ad->getId()));

        $this->adApiResponse[AdFieldMappingInterface::ADDITIONAL_FIELDS] = array();

        if ($object) {
            $metaData = ($object->getMetaData() ? unserialize($object->getMetaData()) : null);
            $paaFields = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($categoryId, $this->container);
            //add auto suggest fields.
            $autoSuggestFields = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:Ad')->getAutoSuggestFields();
            foreach ($autoSuggestFields as $autoSuggestFieldName => $autoSuggestFieldIdName) {
                $autoSuggestFieldIdName = strtolower($autoSuggestFieldIdName);
                $autoSuggestFieldName   = strtolower($autoSuggestFieldName);
                if (isset($paaFields[$autoSuggestFieldIdName])) {
                    $paaFields[$autoSuggestFieldName] = $paaFields[$autoSuggestFieldIdName];
                }
            }

            $key = 0;
            foreach ($paaFields as $field => $label) {
                $value = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($field, $object, $metaData, $this->container, $className);
                if ($value != null && !in_array($label, array_column($this->adApiResponse[AdFieldMappingInterface::ADDITIONAL_FIELDS], 'fieldName'))) {
                    $this->adApiResponse[AdFieldMappingInterface::ADDITIONAL_FIELDS][] = array('fieldName' => $label, 'fieldValue' => $value);
                }
            }
        }
        if ($typeId) {
            $this->adApiResponse[AdFieldMappingInterface::ADDITIONAL_FIELDS][] = array('fieldName' => 'Ad type', 'fieldValue' => $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $typeId));
        }

        //check for private advert
        $isPrivate = false;
        if ($ad->getUser()) {
            $userRole = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserRole($ad->getUser()->getId(), $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER) {
                $isPrivate = true;
            }
        }

        $this->adApiResponse[AdFieldMappingInterface::IS_PRIVATE_ADVERT] = $isPrivate;
    }

    /**
     * Get date.
     *
     * @param integer $timestamp
     *
     * @return string
     */
    protected function getDate($timestamp)
    {
        //$date = new \DateTime($timestamp);
        //return $date->format('Y-m-dTH:i:s.uZ');

        return date('Y-m-d H:i:s', $timestamp);
    }
}
