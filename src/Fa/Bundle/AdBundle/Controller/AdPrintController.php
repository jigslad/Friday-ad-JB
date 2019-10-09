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
use Fa\Bundle\AdBundle\Repository\AdPrintRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\AdBundle\Repository\PrintEditionRepository;
use Fa\Bundle\AdBundle\Repository\AdLocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Repository\ApiTokenRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This controller is used for ad post management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdPrintController extends CoreController
{
    /**
     * Ad print count.
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function getAdPrintCountAction(Request $request)
    {
        $validResponse = $this->validateApiToken($request, ApiTokenRepository::PRINT_API_TYPE_ID);
        if ($validResponse) {
            return $validResponse;
        }
        $searchParam = $request->query->all();

        $count = $this->getRepository('FaAdBundle:AdPrint')->getAdPrintQueryBuilder($searchParam, $this->container)
            ->select('COUNT('.AdPrintRepository::ALIAS.'.id)')
            ->andWhere(AdRepository::ALIAS.'.is_blocked_ad=0')
            ->getQuery()->getSingleScalarResult();

        return new JsonResponse(array('count' => $count));
    }

    /**
     * Ad print count.
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse A Response object.
     */
    public function getAdPrintResultAction(Request $request)
    {
        $validResponse = $this->validateApiToken($request, ApiTokenRepository::PRINT_API_TYPE_ID);
        if ($validResponse) {
            return $validResponse;
        }

        $searchParam   = $request->query->all();
        $printAdArray  = array();
        $buildResponse = $this->get('fa_ad.print.api.response_build');
        $limit         = ((isset($searchParam['Limit']) && $searchParam['Limit'] <= 100) ? intval($searchParam['Limit']) : 100);
        $page          = ((isset($searchParam['Page']) && $searchParam['Page'] > 0) ? intval($searchParam['Page']) : 1);
        $offset        = ($page - 1) * $limit;
        $em            = $this->getEntityManager();

        $adPrintResult = $this->getRepository('FaAdBundle:AdPrint')->getAdPrintQueryBuilder($searchParam, $this->container)
            ->select(AdPrintRepository::ALIAS, AdRepository::ALIAS, PrintEditionRepository::ALIAS, CategoryRepository::ALIAS)
            ->andWhere(AdRepository::ALIAS.'.is_blocked_ad=0')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $adIdsArray = array();
        
        foreach ($adPrintResult as $adPrint) {
            if ($adPrint->getAd()) {
                $adIdsArray[] = $adPrint->getAd()->getId();
            }
        }
                
        $data = array();
        $data['query_filters']['item']['status_id'] = array(EntityRepository::AD_STATUS_LIVE_ID, EntityRepository::AD_STATUS_EXPIRED_ID);
        $data['query_filters']['item']['id'] = $adIdsArray;
        $data['select_fields'] = array('item' => array('id', 'path', 'ord', 'hash', 'aws', 'title', 'category_id', 'main_town_id', 'postcode', 'image_name'));
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
        
        foreach ($adPrintResult as $adPrint) {
            $printAdArray[] = $buildResponse->init($adPrint, $adSolrObjs);
            $adPrint->setTmpPrintQueue(AdPrintRepository::PRINT_QUEUE_STATUS_SENT);
            $em->persist($adPrint);
        }
        
        $em->flush();
        $em->clear();
        $count = $this->getRepository('FaAdBundle:AdPrint')->getAdPrintQueryBuilder($searchParam, $this->container)
            ->select('COUNT('.AdPrintRepository::ALIAS.'.id)')
            ->getQuery()->getOneOrNullResult();
        
        $printApiArray = array(
            'CurrentPage' => (int) $page,
            'TotalPages'  => (!empty($count))?ceil($count[1] / $limit):0,
            'Adverts'     => $printAdArray,
        );

        return new JsonResponse($printApiArray);
        //return $this->render('FaAdBundle:Ad:json.html.twig');
    }
}
