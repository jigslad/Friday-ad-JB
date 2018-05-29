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
use Fa\Bundle\EntityBundle\Entity\LocationGroup;
use Fa\Bundle\EntityBundle\Form\LocationGroupAdminType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Form\LocationGroupSearchAdminType;

/**
 * This controller is used for location group management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 * @version 1.0
 */
class LocationGroupAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     */
    protected function getTableName()
    {
        return 'location_group';
    }

    /**
     * Lists all location group.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaEntityBundle:LocationGroup'), $this->getRepositoryTable('FaEntityBundle:LocationGroup'), 'fa_entity_location_group_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('location_group' => array('id', 'type', 'name'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaEntityBundle:LocationGroup'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(LocationGroupSearchAdminType::class, null, array('action' => $this->generateUrl('location_group_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'          => $this->get('translator')->trans('Location Group'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaEntityBundle:LocationGroupAdmin:index.html.twig', $parameters);
    }

    /**
     * Deletes a record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     */
    public function deleteAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $backUrl       = CommonManager::getAdminBackUrl($this->container);
        $deleteManager = $this->get('fa.deletemanager');
        $entity        = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        $locationGroupCount = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdsCountArray(array($entity->getId()));

        if (isset($locationGroupCount[$entity->getId()])) {
            return $this->handleMessage($this->get('translator')->trans("This record can not be removed from database because it has location assigned to it.", array(), 'error'), $this->getRouteName(''), array(), 'error');
        }
        try {
            $deleteManager->delete($entity);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), $this->getRouteName(''), array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', $this->getRouteName(''));
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), $this->getRouteName(''));
    }
}
