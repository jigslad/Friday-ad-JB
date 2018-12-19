<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\AdBundle\Repository\AdFavoriteRepository;

/**
 * This controller is used for user ads.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class MyFavouriteController extends CoreController
{
    /**
     * Show user ads.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }
        $objLoggedInUser = $this->getLoggedInUser();
        $adIdsArray      = $this->getRepository('FaAdBundle:AdFavorite')->getFavoriteAdByUserId($objLoggedInUser->getId(), $this->container);

        $cookieLocation     = json_decode($this->container->get('request_stack')->getCurrentRequest()->cookies->get('location'), true);
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');

        $keywords       = null;
        $page           = ($request->get('page') ? $request->get('page') : 1);
        $recordsPerPage = 12;
        $parameters     = array();

        if (!empty($adIdsArray) && is_array($adIdsArray)) {
            //set ad criteria to search
            $data['query_filters']['item']['id'] = $adIdsArray;

            // initialize solr search manager service and fetch data based of above prepared search options
            $solrSearchManager = $this->get('fa.solrsearch.manager');
            $solrSearchManager->init('ad', $keywords, $data, $page, $recordsPerPage);
            if (!empty($cookieLocation) && !empty($adIdsArray) && isset($cookieLocation['latitude']) && isset($cookieLocation['longitude'])) {
                $geoDistParams = array('sfield' => 'store', 'pt' => $cookieLocation['latitude'].', '.$cookieLocation['longitude']);
                $this->get('fa.solrsearch.manager')->setGeoDistQuery($geoDistParams);
            }
            $solrResponse = $this->get('fa.solrsearch.manager')->getSolrResponse();
            $result       = $this->get('fa.solrsearch.manager')->getSolrResponseDocs($solrResponse);
            $resultCount  = $this->get('fa.solrsearch.manager')->getSolrResponseDocsCount($solrResponse);
            $this->get('fa.pagination.manager')->init($result, $page, $recordsPerPage, $resultCount);
            $pagination = $this->get('fa.pagination.manager')->getSolrPagination();

            $parameters = array('pagination' => $pagination);
        }

        return $this->render('FaUserBundle:MyFavourite:index.html.twig', $parameters);
    }
}
