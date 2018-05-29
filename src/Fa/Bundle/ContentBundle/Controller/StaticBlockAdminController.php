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
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Form\StaticBlockSearchAdminType;

/**
 * This controller is used for static page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class StaticBlockAdminController extends CrudController implements ResourceAuthorizationController
{

    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'static_block';
    }

    /**
     * Get doctrine entity with namespace.
     *
     * @return string
     */
    protected function getEntityWithNamespace()
    {
        return '\\Fa\\Bundle\\'.ucwords($this->getBundleAlias()).'Bundle\\Entity\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', 'static_page')));
    }

    /**
     * Get name of entity.
     *
     * @return string
     */
    protected function getEntityName()
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', 'static_page')));
    }

    /**
     * Get word to be displayed on template.
     *
     * @return string
     */
    protected function getDisplayWord()
    {
        return ucwords(str_replace('_', ' ', 'static_block'));
    }

    /**
     * Lists all StaticPage entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
        public function indexAction(Request $request)
        {
            CommonManager::setAdminBackUrl($request, $this->container);

            // initialize search filter manager service and prepare filter data for searching
            $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:StaticPage'), $this->getRepositoryTable('FaContentBundle:StaticPage'), 'fa_content_static_block_search_admin');
            $data = $this->get('fa.searchfilters.manager')->getFiltersData();

            // initialize search manager service and fetch data based of filters
            $data['select_fields']  = array('static_page' => array('id', 'title', 'status', 'name'));

            $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:StaticPage'), $data);
            $qb = $this->get('fa.sqlsearch.manager')->getQueryBuilder()
                ->andWhere(StaticPageRepository::ALIAS.'.type IN (:type)')
                ->setParameter('type', array(StaticPageRepository::STATIC_BLOCK_TYPE_ID, StaticPageRepository::STATIC_BLOCK_GA_CODE_ID));

            $query = $qb->getQuery();
            // initialize pagination manager service and prepare listing with pagination based of data
            $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
            $this->get('fa.pagination.manager')->init($query, $page);
            $pagination = $this->get('fa.pagination.manager')->getPagination();

            // initialize form manager service
            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(StaticBlockSearchAdminType::class, null, array('action' => $this->generateUrl('static_block_admin'), 'method' => 'GET'));

            if ($data['search']) {
                $form->submit($data['search']);
            }

            $parameters = array(
                'heading'    => $this->get('translator')->trans('Static Blocks'),
                'form'       => $form->createView(),
                'pagination' => $pagination,
                'sorter'     => $data['sorter'],
            );

            return $this->render('FaContentBundle:StaticBlockAdmin:index.html.twig', $parameters);
        }
}
