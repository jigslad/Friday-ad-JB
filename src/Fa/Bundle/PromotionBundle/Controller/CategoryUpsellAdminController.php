<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\PromotionBundle\Entity\CategoryUpsell;
use Fa\Bundle\PromotionBundle\Form\CategoryUpsellSearchAdminType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PromotionBundle\Form\CategoryUpsellAdminType;

/**
 * This controller is used for category upsell management.
 *
 * @author Chaitra Bhat <chaitra.bhat@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd.
 * @version 1.0
 */
class CategoryUpsellAdminController extends CrudController implements ResourceAuthorizationController
{

    /**
     * SaveNewUsingForm.
     *
     * @var boolean.
     */
    protected $saveNewUsingForm = true;

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
    protected function getTableName()
    {
        return 'category_upsell';
    }

    /**
     * Lists all Entities.
     *
     * @return object.
     */
    public function indexAction(Request $request)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPromotionBundle:CategoryUpsell'), $this->getRepositoryTable('FaPromotionBundle:CategoryUpsell'), 'fa_promotion_category_upsell_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
            'category_upsell' => array(
                'id',
                'price',
                'show_in_filters'
            ),
            'category' => array(
                'id as category',
                'name as category_name'
            ),
            'upsell' => array(
                'id as upsell',
                'title as upsell_name'
            )
        );

        $data['query_joins'] = array(
            'category_upsell' => array(
                'category' => array(
                    'type' => 'inner'
                ),
                'upsell' => array(
                    'type' => 'inner'
                )
            )
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPromotionBundle:CategoryUpsell'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();
        //echo $query->getSql();die;
        
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(CategoryUpsellSearchAdminType::class, null, array(
            'action' => $this->generateUrl('category_upsell_admin'),
            'method' => 'GET'
        ));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading' => 'Category Upsell',
            'form' => $form->createView(),
        );

        $parameters['pagination'] = $pagination;
        $parameters['sorter'] = $data['sorter'];

        return $this->render('FaPromotionBundle:CategoryUpsellAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new category Upsell entity.
     *
     * @param Request $request
     *            Request instance.
     *
     * @return  entity.
     */
    public function createAction(Request $request)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new CategoryUpsell();

        $options = array(
            'action' => $this->generateUrl('category_upsell_create_admin')
        );

        $form = $formManager->createForm(CategoryUpsellAdminType::class, $entity, $options);
       
        if ($formManager->isValid($form)) {
            return parent::handleMessage($this->get('translator')->trans('Category upsell was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'category_upsell_new_admin' : 'category_upsell_admin'));
        }

        $parameters = array(
            'entity' => $entity,
            'form' => $form->createView(),
            'heading' => $this->get('translator')->trans('New Category Upsell')
        );

        return $this->render('FaPromotionBundle:CategoryUpsellAdmin:new.html.twig', $parameters);
    }

    /**
     * Edits an existing entity.
     *
     * @param Request $request
     *            Request instance.
     * @param Integer $id
     *            Id.
     *
     * @throws createNotFoundException.
     */
    final public function updateAction(Request $request, $id)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $entity = $this->getRepository('FaPromotionBundle:CategoryUpsell')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')
                    ->trans('Unable to find category upsell.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'category_upsell_admin');
        }

        $options = array(
            'action' => $this->generateUrl('category_upsell_update_admin', array(
                'id' => $entity->getId()
            )),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(CategoryUpsellAdminType::class, $entity, $options);
        
        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            return parent::handleMessage($this->get('translator')->trans('Category upsell was successfully updated.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'category_upsell_new_admin' : 'category_upsell_admin'));
        }

        $parameters = array(
            'entity' => $entity,
            'form' => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit category upsell')
        );

        return $this->render('FaPromotionBundle:CategoryUpsellAdmin:new.html.twig', $parameters);
    }
    
    /**
     * Deletes a record.
     *
     * @param Request $request
     * @param integer $id
     *
     * @see \Fa\Bundle\AdminBundle\Controller\CrudController::deleteAction()
     */
    public function deleteAction(Request $request, $id)
    {
        $deleteManager = $this->get('fa.deletemanager');
        $entity = $this->getRepository('FaPromotionBundle:CategoryUpsell')->find($id);
        
        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')
                    ->trans('Unable to find category upsell.'));
            } else {
                $deleteManager->delete($entity);
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'category_upsell_admin');
        }
        
        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), $this->getRouteName(''));
    }
}
