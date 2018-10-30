<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Fa\Bundle\CoreBundle\DQL\GroupConcatFunction;
use Fa\Bundle\ContentBundle\Form\BannerSearchAdminType;
use Fa\Bundle\ContentBundle\Form\BannerAdminType;

/**
 * This controller is used for banner management.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BannerAdminController extends CrudController implements ResourceAuthorizationController
{

    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'banner';
    }

    /**
     * Index action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:Banner'), $this->getRepositoryTable('FaContentBundle:Banner'), 'fa_content_banner_search_admin');
        $data               = $this->get('fa.searchfilters.manager')->getFiltersData();
        $selectedPagesArray = array();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
            'banner'      => array('id', 'code'),
            'banner_zone' => array('name as zone_name'),
            'category' => array('name as category_name')
        );

        $data['query_joins'] = array(
            'banner' => array(
                'banner_page' => array('type' => 'left'),
                'banner_zone' => array('type' => 'inner'),
                'category' => array('type' => 'left'),
            )
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:Banner'), $data);
        $qb = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $qb->addSelect("group_concat(".BannerPageRepository::ALIAS.".name, ', ') as page_names");
        $qb->addGroupBy(BannerRepository::ALIAS.'.id');
        $query = $qb->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(BannerSearchAdminType::class, null, array('action' => $this->generateUrl('banner_admin'), 'method' => 'GET'));

        if ($data['search']) {
            if (isset($data['search']['banner_page__id'])) {
                foreach ($data['search']['banner_page__id'] as $key => $pageId) {
                    $selectedPagesArray[] = $pageId;
                }
            }
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'            => $this->get('translator')->trans('Banner'),
            'form'               => $form->createView(),
            'pagination'         => $pagination,
            'sorter'             => $data['sorter'],
            'selectedPagesArray' => $selectedPagesArray
        );

        return $this->render('FaContentBundle:BannerAdmin:index.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     *
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager        = $this->get('fa.formmanager');
        $entity             = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);
        $selectedPagesArray = array();

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        $options =  array(
            'action' => $this->generateUrl($this->getRouteName('update'), array('id' => $entity->getId())),
            'method' => 'PUT'
        );
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);

        $this->unsetFormFields($form);

        $objBannerPages = $entity->getBannerPages();
        foreach ($objBannerPages as $objBannerPage) {
            $selectedPagesArray[] = $objBannerPage->getId();
        }

        $parameters = array(
            'entity'             => $entity,
            'form'               => $form->createView(),
            'heading'            => $this->get('translator')->trans('Edit %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
            'selectedPagesArray' => $selectedPagesArray
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Get pages by zone.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetPagesByZoneAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $bannerPagesArray = array();
            if ($request->get('id')) {
                if (is_array($request->get('id'))) {
                    $zoneIds = $request->get('id');
                } else {
                    $zoneIds = array($request->get('id'));
                }
                foreach ($zoneIds as $zoneId) {
                    $objBannerZone  = $this->getRepository('FaContentBundle:BannerZone')->find($zoneId);
                    $objBannerPages = $objBannerZone->getBannerPages();
                    foreach ($objBannerPages as $objBannerPage) {
                        $pageId             = $objBannerPage->getId();
                        $pageName           = $objBannerPage->getName();
                        $bannerPagesArray[] = array('id' => $pageId, 'text' => $pageName);
                    }
                }

                $uniquePages = array();
                foreach ($bannerPagesArray as $key => $valuesArray) {
                    if (!in_array($valuesArray['id'], $uniquePages)) {
                        $uniquePages[] = $valuesArray['id'];
                        $finalBannerPagesArray[] = array('id' => $valuesArray['id'], 'text' => $valuesArray['text']);
                    }
                }

                return new Response(json_encode($finalBannerPagesArray), 200, array('Content-Type' => 'application/json'));
            } else {
                return new Response();
            }
        }
    }

    public function sortBannerPages($a, $b)
    {
        return $a['text'] - $b['text'];
    }
    
    public function getFQNForForms($formName)
    {
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_content_banner_admin' => BannerAdminType::class
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
    }
}
