<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Entity\ConfigRule;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Form\ConfigRuleSearchAdminType;

/**
 * This controller is used for entity management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ConfigRuleAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Save new using form.
     *
     * @var boolean
     */
    protected $saveNewUsingForm = true;

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'config_rule';
    }

    /**
     * Lists all config rules.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaCoreBundle:ConfigRule'), $this->getRepositoryTable('FaCoreBundle:ConfigRule'), 'fa_core_config_rule_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
                                     'config'         => array('id as config_id', 'name as config_name'),
                                     'config_rule'    => array('id', 'value', 'period_from', 'period_to'),
                                     'category'       => array('id as category_id', 'name as category_name'),
                                     'location_group' => array('name as location_group_name'),
                                 );

        $data['query_joins']   = array(
                                    'config_rule' => array(
                                                        'config'         => array('type' => 'left'),
                                                        'category'       => array('type' => 'left'),
                                                        'location_group' => array('type' => 'left')
                                                        )
                                 );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaCoreBundle:ConfigRule'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(ConfigRuleSearchAdminType::class, null, array('action' => $this->generateUrl('config_rule_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
                          'heading'    => $this->get('translator')->trans('Configuration Rules'),
                          'form'       => $form->createView(),
                          'pagination' => $pagination,
                          'sorter'     => $data['sorter'],
                      );

        return $this->render('FaCoreBundle:ConfigRuleAdmin:index.html.twig', $parameters);
    }

    /**
     * Return child parent category array.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoryAjaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $catArray            = array();
            $catArray['more']    = false;
            $catArray['results'] = $this->getRepository('FaEntityBundle:Category')->getCategoryArrayByText($request->get('term'));
            return new Response(json_encode($catArray), 200, array('Content-Type' => 'application/json'));
        } else {
            return new Response();
        }
    }
}
