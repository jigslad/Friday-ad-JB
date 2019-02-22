<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\AdBundle\Repository\AdLocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Repository\ApiTokenRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This controller is used for ad api.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdApiController extends CoreController
{
    /**
     * Ad Api count.
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function getAdApiCountAction(Request $request)
    {
        $validResponse = $this->validateApiToken($request, ApiTokenRepository::AD_API_TYPE_ID);
        if ($validResponse) {
            return $validResponse;
        }
        $searchParam = $request->query->all();

        $count = $this->getRepository('FaAdBundle:Ad')->getAdApiQueryBuilder($searchParam, $this->container)
            ->select('COUNT('.AdRepository::ALIAS.'.id)')
            ->getQuery()->getSingleScalarResult();

        return new JsonResponse(array('count' => $count));
    }

    /**
     * Ad api count.
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function getAdApiResultAction(Request $request)
    {
        $validResponse = $this->validateApiToken($request, ApiTokenRepository::AD_API_TYPE_ID);
        if ($validResponse) {
            return $validResponse;
        }

        $searchParam   = $request->query->all();
        $adArray  = array();
        $buildResponse = $this->get('fa_ad.ad.api.response_build');
        $limit         = ((isset($searchParam['Limit']) && $searchParam['Limit'] <= 100) ? intval($searchParam['Limit']) : 100);
        $page          = ((isset($searchParam['Page']) && $searchParam['Page'] > 0) ? intval($searchParam['Page']) : 1);
        $offset        = ($page - 1) * $limit;

        $query= $this->getRepository('FaAdBundle:Ad')->getAdApiQueryBuilder($searchParam, $this->container)
            ->select(AdRepository::ALIAS, CategoryRepository::ALIAS, UserRepository::ALIAS)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $adResult = $query->getQuery()->getResult();
        $adIdsArray = array();

        foreach ($adResult as $ad) {
            $adIdsArray[] = $ad->getId();
        }

        $data = array();
        $data['query_filters']['item']['status_id'] = EntityRepository::AD_STATUS_LIVE_ID;
        $data['query_filters']['item']['id'] = $adIdsArray;
        $data['select_fields'] = array('item' => array('id', 'path', 'ord', 'hash', 'aws', 'title', 'category_id', 'main_town_id', 'domicile_id', 'postcode', 'image_name'));
        //set ad criteria to search
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $this->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', null, $data, 1, $limit);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $adSolrDocs = $solrSearchManager->getSolrResponseDocs($solrResponse);
        $adSolrObjs = array();

        foreach ($adSolrDocs as $adSolrDoc) {
            $adSolrObjs[$adSolrDoc[AdSolrFieldMapping::ID]] = $adSolrDoc;
        }

        foreach ($adResult as $ad) {
            $adArray[] = $buildResponse->init($ad, $adSolrObjs);
        }

        $count = $this->getRepository('FaAdBundle:Ad')->getAdApiQueryBuilder($searchParam, $this->container)
        ->select('COUNT('.AdRepository::ALIAS.'.id)')
        ->getQuery()->getSingleScalarResult();

        $adApiArray = array(
            'CurrentPage' => $page,
            'TotalPages'  => ceil($count / $limit),
            'Adverts'     => $adArray,
        );

        return new JsonResponse($adApiArray);
        //return $this->render('FaAdBundle:Ad:json.html.twig');
    }
}
