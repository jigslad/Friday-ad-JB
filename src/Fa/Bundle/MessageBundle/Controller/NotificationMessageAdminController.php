<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\MessageBundle\Form\NotificationMessageSearchAdminType;

/**
 * This controller is used for static page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class NotificationMessageAdminController extends CrudController implements ResourceAuthorizationController
{

    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'notification_message';
    }

    /**
     * Get doctrine entity with namespace.
     *
     * @return string
     */
    protected function getEntityWithNamespace()
    {
        return '\\Fa\\Bundle\\'.ucwords($this->getBundleAlias()).'Bundle\\Entity\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', 'notification_message')));
    }

    /**
     * Index action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaMessageBundle:NotificationMessage'), $this->getRepositoryTable('FaMessageBundle:NotificationMessage'), 'fa_message_notification_message_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('notification_message' => array('id', 'name', 'status', 'message', 'notification_type'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaMessageBundle:NotificationMessage'), $data);
        $qb = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $query = $qb->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(NotificationMessageSearchAdminType::class, null, array('action' => $this->generateUrl('notification_message_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Notification Messages'),
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaMessageBundle:NotificationMessageAdmin:index.html.twig', $parameters);
    }
}
