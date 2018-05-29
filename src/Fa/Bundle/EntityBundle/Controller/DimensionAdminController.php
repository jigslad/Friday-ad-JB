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
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\EntityBundle\Entity\Entity;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Form\DimensionSearchAdminType;

/**
 * This controller is used for entity management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DimensionAdminController extends CrudController implements ResourceAuthorizationController
{

    /**
     * Save using form or not.
     *
     * @var boolean.
     */
    protected $saveNewUsingForm = true;

    /**
     * Get table name.
     */
    protected function getTableName()
    {
        return 'dimension';
    }

    /**
     * Get display name.
     */
    protected function getDisplayWord()
    {
        return $this->get('translator')->trans('Dimension Value');
    }

    /**
     * Get entity namespace.
     */
    protected function getEntityWithNamespace()
    {
        return '\\Fa\\Bundle\\'.ucwords($this->getBundleAlias()).'Bundle\\Entity\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', 'entity')));
    }

    /**
     * Get entity name.
     */
    protected function getEntityName()
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', 'entity')));
    }

    /**
     * Lists all category dimensions.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaEntityBundle:Entity'), $this->getRepositoryTable('FaEntityBundle:Entity'), 'fa_entity_dimension_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('entity' => array('id', 'name'), 'category_dimension' => array('name as category_dimension_name'));
        $data['query_joins']    = array('entity' => array('category_dimension' => array('type' => 'left')));
        $data['static_filters'] = \Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository::ALIAS.'.category IS NOT NULL';

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaEntityBundle:Entity'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(DimensionSearchAdminType::class, null, array('action' => $this->generateUrl('dimension_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'entityTypeArray' => $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionByCategoryArray(),
            'heading'         => $this->get('translator')->trans('Dimension Values'),
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
        );

        return $this->render('FaEntityBundle:DimensionAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new location entity.
     *
     * @param Request $request Request instance.
     *
     * @return JsonResponse.
     */
    public function ajaxGetDimensionAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $categoryId = $request->get('category_id');
            if ($categoryId) {
                $options['dimension'] = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionByCategoryHierarchyArray($categoryId);
                return new JsonResponse($options);
            }
        }

        return new JsonResponse();
    }
}
