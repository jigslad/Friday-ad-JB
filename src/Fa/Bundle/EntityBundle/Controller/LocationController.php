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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Fa\Bundle\EntityBundle\Entity\Location;
use Fa\Bundle\EntityBundle\Form\LocationType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\LocationPostalRepository;

/**
 * This controller is used for admin side location management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class LocationController extends CoreController
{
    /**
     * Get ajax a location nodes.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId        = (int) trim($request->get('id'));
            $childrens     = $this->getRepository('FaEntityBundle:Location')->getChildrenById($nodeId);
            $childrenArray = array();

            foreach ($childrens as $children) {
                $childrenArray[] = array('id' => $children['id'], 'text' => $children['name'], 'children' => ($children['rgt'] - $children['lft'] > 1));
            }

            return new JsonResponse($childrenArray);
        } else {
            return new Response();
        }
    }

    /**
     * Get unset form fields.
     */
    protected function getUnsetFormFields()
    {
        $fields = array(
                   'latitude',
                   'longitude',
                   'lft',
                   'rgt',
                   'root',
                   'parent',
                   'lvl',
                  );

        return $fields;
    }

    /**
     * Get ajax a location nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeJsonAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = $request->get('id');
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId($nodeId);
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
     * Get ajax a location nodes in json format.
     *
     * @param Request $request Request instance
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetNodeJsonForLocationGroupAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $nodeId = $request->get('id');
            $locationGroupId = $request->get('locationGroupId', null);
            $locationGroupType = $request->get('locationGroupType', null);
            $locationField = $request->get('locationField');
            if ($nodeId) {
                $childrens     = $this->getRepository('FaEntityBundle:Location')->getChildrenKeyValueArrayByParentId($nodeId);
                $childrenArray = array();
                $locationGroupFieldIds = array();
                if ($locationField == 'town') {
                    $locationGroupFieldIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getTownArrayByLocationGroupType($locationGroupType);
                } elseif ($locationField == 'domicile') {
                    $locationGroupFieldIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getDomicileArrayByLocationGroupId($locationGroupId, $locationGroupType);
                }
                foreach ($childrens as $id => $name) {
                    if (!in_array($id, $locationGroupFieldIds)) {
                        $childrenArray[] = array('id' => $id, 'text' => $name);
                    }
                }

                return new JsonResponse($childrenArray);
            }
        }

        return new Response();
    }

    /**
     * Get towns with locality ajax action.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetTownsByTermAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $townArray            = array();
            $localityArray        = array();
            $areaArray			  = array();
            $areaArray			  = array();
            $townArray['more']    = false;
            $townArray['results'] = $this->getRepository('FaEntityBundle:Location')->getTownsArrayByTerm($request->get('term'));
            if (count($townArray['results']) < 5) {
                $localityArray['results'] = $this->getRepository('FaEntityBundle:Locality')->getLocalitiesArrayByTerm($request->get('term'));
                $townArray['results'] = $townArray['results'] + $localityArray['results'];
                
                //get all areas based on user suggestion
                $areaArray['results'] = $this->getRepository('FaEntityBundle:Location')->getTownsAreaArrayByTerm($request->get('term'), 4);
                $townArray['results'] = array_merge($townArray['results'], $areaArray['results']);
            }
            
            //check post code belongs to london area
            if (empty($townArray['results'])) {
                $postCode = $this->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($request->get('term'));
                if ($postCode && $postCode->getTownId()) {
                    $getTownById = $this->getRepository('FaEntityBundle:Location')->find($postCode->getTownId());
                    if ($getTownById && $getTownById->getLvl() == 4 || $postCode->getTownId() == LocationRepository::LONDON_TOWN_ID) {
                        $getPostalCode = explode(" ", $request->get('term'));
                        $townArray['results'] = $this->getRepository('FaEntityBundle:LocationPostal')->getTownAreasArrayByPostCode($getPostalCode['0']);
                    }
                }
            }
            return new JsonResponse($townArray);
        }

        return new Response();
    }

    /**
     * Get only towns without locality ajax action.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetTownsOnlyByTermAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $townArray            = array();
            $localityArray        = array();
            $townArray['more']    = false;
            $townArray['results'] = $this->getRepository('FaEntityBundle:Location')->getTownsArrayByTerm($request->get('term'));

            return new JsonResponse($townArray);
        }

        return new Response();
    }

    /**
     * Show all locations.
     *
     * @param Request $request Request instance.
     *
     * @return Response A Response object.
     */
    public function showAllLocationAction(Request $request)
    {
        $parameters = array(
        );

        return $this->render('FaEntityBundle:Location:showAllLocation.html.twig', $parameters);
    }

    /**
     * Show all towns by county.
     *
     * @param Request $request Request instance.
     *
     * @return Response A Response object.
     * @throws NotFoundHttpException
     */
    public function showAllTownsByCountyAction(Request $request)
    {
        $countySlug = $request->get('countySlug');
        $countyId   = $this->getRepository('FaEntityBundle:Location')->getIdBySlug($countySlug, $this->container);
        $countyInfo  = $this->getRepository('FaEntityBundle:Location')->getCountyInfoArrayById($countyId);
        if (!$countyInfo) {
            throw new NotFoundHttpException('Invalid county.');
        }

        $parameters = array(
            'countyId' => $countyId,
            'countyInfo' => $countyInfo,
        );

        return $this->render('FaEntityBundle:Location:showAllTownsByCounty.html.twig', $parameters);
    }

    /**
     * Get counties ajax action.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetCountiesByTermAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $countyArray            = array();
            $countyArray['more']    = false;
            $countyArray['results'] = $this->getRepository('FaEntityBundle:Location')->getCountiesArrayByTerm($request->get('term'));

            return new JsonResponse($countyArray);
        }

        return new Response();
    }
    
    /**
     * Get Areas By Town.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetAreasByTownAndTermAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $townArray            = array();
            $localityArray        = array();
            $areaArray['more']    = false;
            $getPostalDistrict = explode(' ', $request->get('term'));
            $areaArray['results'] = $this->getRepository('FaEntityBundle:LocationPostal')->getTownAreasArrayByPostCode($getPostalDistrict[0]);
            
            return new JsonResponse($areaArray);
        }
        
        return new Response();
    }
}
