<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EntityBundle\Form\FaEntityLogSearchAdminType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\FaEntityLogRepository;

/**
 * This controller is used for entity management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 * @version v1.0
 */
class FaEntityLogAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getTiHistoryRepository('FaTiReportBundle:FaEntityLog'), $this->getTiHistoryRepositoryTable('FaTiReportBundle:FaEntityLog'), 'fa_entity_log_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $query = $this->getTiHistoryRepository('FaTiReportBundle:FaEntityLog')->getEntityLogReportQuery($data['search'], $data['sorter'], $this->container);

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(FaEntityLogSearchAdminType::class, null, array('action' => $this->generateUrl('fa_ti_entity_log'), 'method' => 'GET', 'em' => $this->getEntityManager()));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'         => $this->get('translator')->trans('TI Entity Log'),
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
        );

        return $this->render('FaTiReportBundle:FaEntityLogAdmin:index.html.twig', $parameters);
    }

    /**
     * Lists all entities.
     *
     * @return Response A Response object.
     */
    public function detailAction(Request $request, $id)
    {
      CommonManager::setAdminBackUrl($request, $this->container);
      // initialize form manager service

      $entity_log = $this->getTiHistoryRepository('FaTiReportBundle:FaEntityLog')->find($id);

      try {
        if (!$entity_log) {
          throw $this->createNotFoundException($this->get('translator')->trans('Unable to find entity log entry.'));
        }
      } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        return parent::handleException($e, 'error', 'fa_ti_entity_log');
      }

      $parameters = array(
          'entity_log'  => $entity_log,
          'heading' => $this->get('translator')->trans('TI View Detail'),
      );

      return $this->render('FaTiReportBundle:FaEntityLogAdmin:detail.html.twig', $parameters);
    }

    /**
     * Get unset form fields.
     */
    protected function getUnsetFormFields()
    {
        $fields = array();

        return $fields;
    }

    /**
     * Save form fieldse.
     *
      * @param $form Form.
     */
    protected function addFormFields($form)
    {
        $form->add('save', 'submit');
    }
}
