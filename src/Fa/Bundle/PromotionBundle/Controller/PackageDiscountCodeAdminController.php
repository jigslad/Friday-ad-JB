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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PromotionBundle\Entity\PackageDiscountCode;
use Fa\Bundle\PromotionBundle\Form\PackageDiscountCodeSearchAdminType;
use Fa\Bundle\PromotionBundle\Form\PackageDiscountCodeAdminType;

/**
 * This controller is used for package discount code management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageDiscountCodeAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all package discount codes.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPromotionBundle:PackageDiscountCode'), $this->getRepositoryTable('FaPromotionBundle:PackageDiscountCode'), 'fa_promotion_package_discount_code_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('package_discount_code' => array('id', 'code', 'discount_type', 'discount_value', 'package_sr_no', 'role_ids', 'status'), 'category' => array('id as category_id'));

        $data['query_joins']   = array(
            'package_discount_code' => array(
                'category' => array('type' => 'left', 'condition_type' => 'WITH'),
            ),
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPromotionBundle:PackageDiscountCode'), $data);
        $query = $this->get('fa.sqlsearch.manager');
        $query = $query->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(PackageDiscountCodeSearchAdminType::class, null, array('action' => $this->generateUrl('package_discount_code_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('Package discount codes'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaPromotionBundle:PackageDiscountCodeAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new package.
     *
     * @param Request $request A Request object.
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new PackageDiscountCode();

        $options =  array(
                      'action' => $this->generateUrl('package_discount_code_create_admin'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(PackageDiscountCodeAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('Package discount code was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'package_discount_code_new_admin' : ($backUrl ? $backUrl : 'package_discount_code_admin')));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New package discount code'),
                      );

        return $this->render('FaPromotionBundle:PackageDiscountCodeAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new package.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function newAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new PackageDiscountCode();

        $form = $formManager->createForm(PackageDiscountCodeAdminType::class, $entity, array('action' => $this->generateUrl('package_discount_code_create_admin')));

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New package discount code'),
                      );

        return $this->render('FaPromotionBundle:PackageDiscountCodeAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing package.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package discount code.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'package_discount_code_admin');
        }

        $options =  array(
                      'action' => $this->generateUrl('package_discount_code_update_admin', array('id' => $entity->getId())),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm(PackageDiscountCodeAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit package discount code'),
                      );

        return $this->render('FaPromotionBundle:PackageDiscountCodeAdmin:new.html.twig', $parameters);
    }

    /**
     * Edits an existing package.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package discount code.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'package_discount_code_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('package_discount_code_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(PackageDiscountCodeAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage($this->get('translator')->trans('Package discount code was successfully updated.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'package_discount_code_new_admin' : ($backUrl ? $backUrl : 'package_discount_code_admin')));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit package discount code'),
                       );

        return $this->render('FaPromotionBundle:PackageDiscountCodeAdmin:new.html.twig', $parameters);
    }

    /**
     * Deletes a package.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);

        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaPromotionBundle:PackageDiscountCode')->find($id);

        $userentity = $this->getRepository('FaPromotionBundle:UserPackageDiscountCode')->getTotalUsedCountForCode($id);
        
        try {
            if ($userentity>0) {
                throw $this->createNotFoundException($this->get('translator')->trans("This record can not be removed beacuse it's reference exists in user package discount.", array(), 'error'));
            } else {
                if (!$entity) {
                    throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package discount code.'));
                }
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'package_discount_code_admin');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage($this->get('translator')->trans('Package discount code was successfully deleted.', array(), 'success'), ($backUrl ? $backUrl : 'package_discount_code_admin'));
    }
}
