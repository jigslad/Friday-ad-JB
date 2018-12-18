<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\LocationRadiusRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This controller is used for location radius management.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class LocationRadiusAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'location_radius';
    }

    /**
     * Lists all SeoTool entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:LocationRadius'), $this->getRepositoryTable('FaAdBundle:LocationRadius'), 'fa_ad_location_radius_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['query_joins']   = array(
            'location_radius' => array(
                'category' => array('type' => 'left'),
            )
        );
        $data['select_fields']  = array(
            'location_radius' => array('id', 'defaultRadius', 'extendedRadius', 'status'),
            'category' => array('name as category_name')
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:LocationRadius'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $queryBuilder->distinct(LocationRadiusRepository::ALIAS.'.id');
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm('fa_ad_location_radius_search_admin', null, array('action' => $this->generateUrl('location_radius_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Category Radius'),
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaAdBundle:LocationRadiusAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new record.
     *
     * @param Request $request A request object.
     *
     * @return Response A response object.
     */
    public function createAction(Request $request)
    {
        $entity      = $this->getEntity();
        $formManager = $this->get('fa.formmanager');

        $options =  array(
            'action' => $this->generateUrl($this->getRouteName('create')),
            'method' => 'POST'
        );

        $form = $formManager->createForm('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin', $entity, $options);

        if ($formManager->isValid($form)) {
            return $this->handleMessage($this->get('translator')->trans('%displayWord% was successfully added.', array('%displayWord%' => $this->getDisplayWord()), 'success'), ($form->get('saveAndNew')->isClicked() ? $this->getRouteName('new') : $this->getRouteName('')));
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Edits an existing record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl     = CommonManager::getAdminBackUrl($this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

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

        $form = $formManager->createForm('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin', $entity, $options);

        if ($formManager->isValid($form)) {
            $messageManager = $this->get('fa.message.manager');
            $messageManager->setFlashMessage($this->get('translator')->trans('%displayword% was successfully updated.', array('%displayword%' => $this->getDisplayWord())), 'success');
            if (empty($backUrl)) {
                $backUrl = $this->generateUrl('location_radius_admin');
            }
            return $this->redirect($backUrl);
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit %displayword%', array('%displayword%' => $this->getDisplayWord())),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }
}
