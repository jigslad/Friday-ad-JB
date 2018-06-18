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
use Fa\Bundle\AdBundle\Entity\PaaFieldRule;
// use Fa\Bundle\AdBundle\Form\PaaFieldRuleType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Form\PaaFieldRuleAdminSearchType;

/**
 * This controller is used for paa field rule crud management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PaaFieldRuleAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * SaveNewUsingForm.
     *
     * @var boolean.
     */
    protected $saveNewUsingForm  = true;

    /**
     * Save edit using form.
     *
     * @var boolean
     */
    protected $saveEditUsingForm = true;

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'paa_field_rule';
    }

    /**
     * Lists all PaaFieldRule entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:PaaFieldRule'), $this->getRepositoryTable('FaAdBundle:PaaFieldRule'), 'fa_ad_paa_field_rule_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
                                    'paa_field_rule' => array('id'),
                                    'category'       => array('id as category_id','lvl as category_lvl'),
                                 );

        $data['query_joins'] = array(
                                   'paa_field_rule' => array(
                                                         'category'  => array('type' => 'left'),
                                                         'paa_field' => array('type' => 'left'),
                                                     )
                               );


        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:PaaFieldRule'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();

        //fetch paa field TITLE id for comparision
        $paaField = $this->getRepository('FaAdBundle:PaaField')->findOneBy(array('field' => 'title'));

        // display only one entry for categorywise rule
        $queryBuilder->andWhere($queryBuilder->getRootAlias().'.paa_field = '.$paaField->getId());
        $queryBuilder->andWhere($queryBuilder->getRootAlias().'.category != '.CategoryRepository::PHONE_AND_CAM_CHAT_ID);
        $query = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(PaaFieldRuleAdminSearchType::class, null, array('action' => $this->generateUrl('paa_field_rule_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => 'PAA field rules',
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaAdBundle:PaaFieldRuleAdmin:index.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new record.
     *
     * @param integer $category_id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newFromCategoryAction($category_id, Request $request)
    {
        $entity      = $this->getEntity();
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin', $entity, array('action' => $this->generateUrl($this->getRouteName('create'))));

        $parameters = array(
                           'entity'  => $entity,
                           'form'    => $form->createView(),
                           'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
                      );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Deletes a record.
     *
     * @param Request $request
     * @param integer $id
     *
     * @throws createNotFoundException
     * @see \Fa\Bundle\AdminBundle\Controller\CrudController::deleteAction()
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl       = CommonManager::getAdminBackUrl($this->container);
        $deleteManager = $this->get('fa.deletemanager');
        $paaFieldRules = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryId($id);

        $category = null;
        if ($id) {
            $category = $this->getRepository('FaEntityBundle:Category')->find($id);
        }

        try {
            if ($category->getLvl() == 1) {
                throw $this->createNotFoundException($this->get('translator')->trans('Paa field rule can not be deleted.'));
            }

            if (!count($paaFieldRules)) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        foreach ($paaFieldRules as $paaFieldRule) {
            $deleteManager->delete($paaFieldRule);
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }

    /**
     * Displays all PAA fields rule of given category.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, $id)
    {
        $paaFieldRules = $this->getRepository('FaAdBundle:PaaFieldRule')->getPaaFieldRulesByCategoryId($id);
        $parameters    = array(
                             'paaFieldRules' => $paaFieldRules,
                             'categoryId'    => $id,
                             'heading'       => $this->get('translator')->trans('PAA field rules'),
                         );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':show.html.twig', $parameters);
    }
}
