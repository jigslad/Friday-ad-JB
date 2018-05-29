<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used for list category dimesions.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CategoryDimensionController extends CoreController
{
    /**
     * Lists all Category dimensions.
     *
     * @param string $categoryString Category slug.
     * @param string $dimensionName  Dimension name.
     * @param object $request        Request object.
     *
     * @return Response A Response object.
     */
    public function showCategoryDimensionAction($categoryString, $dimensionName, Request $request)
    {
        $dimensionFacets = array();
        $categoryId      = $this->getRepository('FaEntityBundle:Category')->getIdBySlug($categoryString, $this->container);

        if (!$categoryId) {
            throw new NotFoundHttpException('Invalid category.');
        }

        $parentCategoryIds = array_keys($this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container));

        //get location from cookie.
        $locationDetails = json_decode($request->cookies->get('location'), true);
        if (!isset($locationDetails['location'])) {
            $locationDetails['location'] = null;
        }
        // set search params.
        $seoSearchParams['item__category_id'] = $categoryId;
        $seoSearchParams['item__location']    = $locationDetails['location'];

        if (isset($parentCategoryIds[1]) && in_array($parentCategoryIds[1], array(CategoryRepository::CARS_ID, CategoryRepository::COMMERCIALVEHICLES_ID))) {
            unset($seoSearchParams['item__category_id']);
            $categoryDimension  = $this->getRepository('FaEntityBundle:CategoryDimension')->findOneBy(array('name' => $dimensionName));
            if (!$categoryDimension) {
                throw new NotFoundHttpException('Invalid dimension.');
            }
            $dimensions         = array();
            $dimensionFacets    = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimensionFacetBySearchParams('category_make', $seoSearchParams, array(), $this->container, false, $parentCategoryIds[1], ' AND (a_parent_category_lvl_2_id_i : '.$parentCategoryIds[1].')');
            $dimensions         = $this->getRepository('FaEntityBundle:Category')->getCategoriesByIds(array_keys($dimensionFacets));
            $dimensionFieldName = 'item__category_id';
            $isCategoryMake     = true;
        } else {
            $categoryDimension   = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimensionByNameAndCategory($categoryId, $dimensionName);
            if (!$categoryDimension) {
                $tmpParentCategoryIds = $parentCategoryIds;
                $tmpParentCategoryIds = array_reverse($tmpParentCategoryIds);
                if (isset($tmpParentCategoryIds[0])) {
                    unset($tmpParentCategoryIds[0]);
                }
                foreach ($tmpParentCategoryIds as $tmpParentCategoryId) {
                    $categoryDimension = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimensionByNameAndCategory($tmpParentCategoryId, $dimensionName);
                    if ($categoryDimension) {
                        break;
                    }
                }
            }
            if (!$categoryDimension) {
                throw new NotFoundHttpException('Invalid dimension.');
            }

            $dimensions          = array();
            $indexableDimensions = $this->getRepository('FaEntityBundle:CategoryDimension')->getIndexableDimensionFieldsArrayByCategoryId($categoryId, $this->container);
            $isCategoryMake      = false;

            if (count($indexableDimensions)) {
                $dimensionFieldName = isset($indexableDimensions[$categoryDimension->getName()]) ? $indexableDimensions[$categoryDimension->getName()] : null;
                if ($dimensionFieldName) {
                    $dimensionFacets = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimensionFacetBySearchParams($dimensionFieldName, $seoSearchParams, array(), $this->container);
                    $dimensions = $this->getRepository('FaEntityBundle:Entity')->getEntitiesByIds(array_keys($dimensionFacets));
                }
            }
        }

        $parameters = array(
            'categoryDimension'  => $categoryDimension,
            'dimensions'         => $dimensions,
            'seoSearchParams'    => $seoSearchParams,
            'dimensionFacets'    => $dimensionFacets,
            'dimensionFieldName' => $dimensionFieldName,
            'isCategoryMake'     => $isCategoryMake,
        );

        return $this->render('FaEntityBundle:CategoryDimension:showCategoryDimension.html.twig', $parameters);
    }
}
