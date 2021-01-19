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
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Entity\Category;
use Symfony\Component\Console\Command\Command;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Doctrine\ORM\Query;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This controller is used for admin side category management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class CategoryController extends CoreController
{
    /**
     * Lists all Category entities.
     *
     * @return Response A Response object.
     */
    public function renderFooterCategoriesAction(Request $request, $location = null, $searchParams = [])
    {
        //$locationDetails = CommonManager::getLocationDetailFromParamsOrCookie($location, $request, $this->container);
        $locationDetails = array();
        if (!isset($locationDetails['location'])) {
            $locationDetails['location'] = null;
        }
        $footerCategories = $this->getRepository('FaEntityBundle:Category')->getFooterCategories($this->container, $locationDetails);
        $parameters       = array('footerCategories' => $footerCategories, 'location_id' => $locationDetails['location']);

        // fetch location directly
        if (!$parameters['location_id']) {
            $parameters['location_id'] = 'uk';
        } else {
            $parameters['location_id'] = $locationDetails['slug'];
        }

        $parameters['location'] = $locationDetails['location'];
        $parameters['searchParams'] = $searchParams;

        return $this->render('FaEntityBundle:Category:renderFooterCategories.html.twig', $parameters);
    }

    /**
     * Lists all Category entities.
     *
     * @param boolean $is_tablet
     * @param boolean $is_mobile
     *
     * @return Response A Response object.
     */
    public function renderHeaderCategoriesAction(Request $request, $is_tablet = 0, $is_mobile = 0, $location = null, $searchParams = [])
    {
        $thirdPartyAdultModalBox = false;
        if (isset($searchParams['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($searchParams['item__category_id'], $this->container);
            if ($rootCategoryId == CategoryRepository::ADULT_ID) {
                $thirdPartyAdultModalBox = true;
            }
        } elseif ($request->cookies->has('is_over_18')) {
            $thirdPartyAdultModalBox = true;
        }
        
        
        
        $locationDetails = CommonManager::getLocationDetailFromParamsOrCookie($location, $request, $this->container);
        if (!empty($locationDetails)) {
            if ($locationDetails['location']!='') {
                $splitLocation = explode(',', $locationDetails['location']);
                if (count($splitLocation)>1) {
                    $locationDetails = $this->getRepository('FaEntityBundle:Location')->getArrayByTownId($locationDetails['town_id']);
                }
            }
        }

        $headerCategories = $this->getRepository('FaEntityBundle:Category')->getHeaderCategories($this->container, $locationDetails,$searchParams);

        if (!isset($locationDetails['location'])) {
            $locationDetails['location'] = null;
            $locationDetails['locality'] = null;
            $locationDetails['locality_id'] = null;
            $locationDetails['slug'] = null;
        }
        /* if(isset($locationDetails1)) {
             $parameters = array('headerCategories' => $headerCategories, 'isTablet' => $is_tablet, 'isMobile' => $is_mobile, 'location_id' => $locationDetails1['location']);
             $parameters['location'] = $locationDetails1['location'];
             $parameters['location_id'] = $locationDetails1['slug'];
         } else {*/
        $parameters = array('headerCategories' => $headerCategories, 'isTablet' => $is_tablet, 'isMobile' => $is_mobile, 'location_id' => $locationDetails['location']);
        $parameters['location'] = $locationDetails['location'];
        $parameters['location_id'] = $locationDetails['slug'];
        //}

        // $parameters['locality'] = $locationDetails['locality'];
        //$parameters['locality_id'] = $locationDetails['locality_id'];
        // fetch location directly
        if (!$parameters['location_id']) {
            $parameters['location_id'] = 'uk';
            $parameters['location'] = $this->getRepository('FaEntityBundle:Location')->getIdBySlug('uk');
        }
        $parameters['thirdPartyAdultModalBox'] = $thirdPartyAdultModalBox;
        

        /*if ($is_mobile) {
            ini_set('xdebug.max_nesting_level', 120);
            return $this->render('FaEntityBundle:Category:renderHeaderCategoriesForMobile.html.twig', $parameters);
        } else {*/
            return $this->render('FaEntityBundle:Category:renderHeaderCategories.html.twig', $parameters);
        //}
    }

    /**
     * Get ajax a category nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeJsonAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = (int) trim($request->get('id'));
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Category')->getChildrenKeyValueArrayByParentId($nodeId, $this->container);
                $childrenArray = array();

                foreach ($childrens as $id => $name) {
                    $childrenArray[] = array('id' => $id, 'text' => $name);
                }

                return new JsonResponse($childrenArray);
            }
        }

        return new Response();
    }


    /**
     * Get ajax a category nested nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetPostadCategoryAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $catArray            = array();
            $catArray['more']    = false;
            $catArray['results'] = $this->getRepository('FaEntityBundle:Category')->getPostadCategoryArrayByText($request->get('term'), $this->container);

            return new JsonResponse($catArray);
        }

        return new Response();
    }

    /**
     * Get ajax a category nested nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNestedNodeJsonAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = (int) trim($request->get('id'));
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Category')->getNestedChildrenKeyValueArrayByParentId($nodeId);
                $childrenArray = array();

                foreach ($childrens as $id => $name) {
                    $childrenArray[] = array('id' => $id, 'text' => $name);
                }

                return new JsonResponse($childrenArray);
            }
        }

        return new Response();
    }

    /**
     * Get category name by id.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetCategoryNameByIdAction(Request $request)
    {
        $name = '';
        if ($request->isXmlHttpRequest()) {
            $nodeId = (int) trim($request->get('id'));
            if ($nodeId) {
                $objCategory = $this->getRepository('FaEntityBundle:Category')->findOneById($nodeId);
                if ($objCategory) {
                    $childrenArray[] = array('id' => $nodeId, 'text' => $objCategory->getName());
                    return new JsonResponse($childrenArray);
                }
            }
        }

        return new Response();
    }
    
    /**
     * Lists all Adult Category entities.
     *
     * @param boolean $is_tablet
     * @param boolean $is_mobile
     *
     * @return Response A Response object.
     */
    public function renderAdultHeaderCategoriesAction(Request $request, $is_tablet = 0, $is_mobile = 0, $location = null, $searchParams = [])
    {
        $thirdPartyAdultModalBox = false;
        if (isset($searchParams['item__category_id'])) {
            $rootCategoryId = $this->getRepository('FaEntityBundle:Category')->getRootCategoryId($searchParams['item__category_id'], $this->container);
            if ($rootCategoryId == CategoryRepository::ADULT_ID) {
                $thirdPartyAdultModalBox = true;
            }
        } elseif ($request->cookies->has('is_over_18')) {
            $thirdPartyAdultModalBox = true;
        }
        
        $locationDetails = CommonManager::getLocationDetailFromParamsOrCookie($location, $request, $this->container);
        if (!empty($locationDetails)) {
            if ($locationDetails['location']!='') {
                $splitLocation = explode(',', $locationDetails['location']);
                if (count($splitLocation)>1) {
                    $locationDetails = $this->getRepository('FaEntityBundle:Location')->getArrayByTownId($locationDetails['town_id']);
                }
            }
        }

        $distance = '';
        if (isset($searchParams['item__distance'])) {
            $distance = $searchParams['item__distance'];
        }
        $headerCategories = $this->getRepository('FaEntityBundle:Category')->getAdultHeaderCategories($this->container, $locationDetails, $distance);
        
        if (!isset($locationDetails['location'])) {
            $locationDetails['location'] = null;
            $locationDetails['locality'] = null;
            $locationDetails['locality_id'] = null;
            $locationDetails['slug'] = null;
        }
        $parameters = array('headerCategories' => $headerCategories, 'isTablet' => $is_tablet, 'isMobile' => $is_mobile, 'location_id' => $locationDetails['location']);
        $parameters['location'] = $locationDetails['location'];
        $parameters['location_id'] = $locationDetails['slug'];
         // fetch location directly
        if (!$parameters['location_id']) {
            $parameters['location_id'] = 'uk';
            $parameters['location'] = $this->getRepository('FaEntityBundle:Location')->getIdBySlug('uk');
        }
        $parameters['thirdPartyAdultModalBox'] = $thirdPartyAdultModalBox;

        return $this->render('FaFrontendBundle:Adult:renderHeaderCategories.html.twig', $parameters);
    }
}
