<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ArchiveBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\ArchiveBundle\Entity\ArchiveAd;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\ArchiveBundle\Repository\ArchiveAdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Doctrine\ORM\Query;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ArchiveBundle\Form\ArchiveAdSearchAdminType;

/**
 * This controller is used for archive ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ArchiveAdAdminController extends CoreController implements ResourceAuthorizationController
{

    /**
     * Lists all Ad entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaArchiveBundle:ArchiveAd'), $this->getRepositoryTable('FaArchiveBundle:ArchiveAd'), 'fa_archive_archive_ad_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['select_fields'] = array('archive_ad' => array('id', 'user_id', 'archived_at', 'ad_data', 'ad_view_counter', 'email'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaArchiveBundle:ArchiveAd'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();

        $query = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(ArchiveAdSearchAdminType::class, null, array('action' => $this->generateUrl('archive_ad_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'     => 'Archive Ads',
            'form'        => $form->createView(),
            'pagination'  => $pagination,
            'sorter'      => $data['sorter'],
        );

        return $this->render('FaArchiveBundle:ArchiveAdAdmin:index.html.twig', $parameters);
    }

    /**
     * Ad detail.
     *
     * @param integer $id
     * @param Request $request
     *
     * @throws createNotFoundException
     * @return Response A Response object.
     */
    public function adDetailAction($id, Request $request)
    {
        $archiveAd = $this->getRepository('FaArchiveBundle:ArchiveAd')->find($id);

        try {
            if (!$archiveAd) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_admin');
        }

        $adDetail = $this->getRepository('FaArchiveBundle:ArchiveAd')->getAdDetailArray($archiveAd, $this->container);
        
        //To fix archive ad missing image fields error
        if(isset($adDetail['images']))
        {
            foreach($adDetail['images'] as $key => $val) {
                if(empty($adDetail['images'][$key]['aws'])) {
                    $adDetail['images'][$key]['aws'] = '';
                }
                if(empty($adDetail['images'][$key]['image_name'])) {
                    $adDetail['images'][$key]['image_name'] = '';
                }
            }
        }

        $parameters = array(
            'ad' => $archiveAd,
            'adDetail' => $adDetail,
        );

        return $this->render('FaArchiveBundle:ArchiveAdAdmin:adDetail.html.twig', $parameters);
    }
}
