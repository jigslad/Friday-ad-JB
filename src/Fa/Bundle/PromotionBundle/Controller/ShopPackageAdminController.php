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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Entity\Package;
// use Fa\Bundle\PromotionBundle\Form\ShopPackageType;
// use Fa\Bundle\PromotionBundle\Form\PackageSearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\PromotionBundle\Form\ShopPackageAdminType;

/**
 * This controller is used for package management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ShopPackageAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all Entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPromotionBundle:Package'), $this->getRepositoryTable('FaPromotionBundle:Package'), 'fa_promotion_package_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('package' => array('id', 'title', 'price', 'status'));

        $data['query_joins']   = array(
            'package' => array(
                'package_rule' => array('type' => 'left', 'condition_type' => 'WITH'),
            ),
            'package_rule' => array(
                'category' => array('type' => 'left', 'condition_type' => 'WITH'),
            ),
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPromotionBundle:Package'), $data);
        $query = $this->get('fa.sqlsearch.manager');
        $query->getQueryBuilder()->addGroupBy(PackageRepository::ALIAS.'.id');
        $query->getQueryBuilder()->andWhere(PackageRepository::ALIAS.'.package_for = :package');
        $query->getQueryBuilder()->setParameter('package', 'shop');
        $query = $query->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $packageIdArray = array();
        $categoryLocationArray = array();
        if ($pagination->getNbResults()) {
            foreach ($pagination->getCurrentPageResults() as $package) {
                $packageIdArray[] = $package['id'];
            }
            array_unique($packageIdArray);
            $categoryLocationArray = $this->getRepository('FaPromotionBundle:PackageRule')->getCategoryLocationGroupByPackageIds($packageIdArray);
        }

        $parameters = array(
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('Subscription'),
            'pagination'       => $pagination,
            'categoryLocationArray' => $categoryLocationArray,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaPromotionBundle:ShopPackageAdmin:index.html.twig', $parameters);
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
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Package();

        $options =  array(
                      'action' => $this->generateUrl('shop_package_create_admin'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(ShopPackageAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);
            $this->container->get('package_created_log')->info('shop package created in package admin create function' . $entity->getId());
            return parent::handleMessage($this->get('translator')->trans('Subscription was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'shop_package_new_admin' : 'shop_package_admin'));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New subscription'),
                      );

        return $this->render('FaPromotionBundle:ShopPackageAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new package.
     *
     * @return Response A Response object.
     */
    public function newAction()
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new Package();

        $form = $formManager->createForm(ShopPackageAdminType::class, $entity, array('action' => $this->generateUrl('shop_package_create_admin')));

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New subscription'),
                      );

        return $this->render('FaPromotionBundle:ShopPackageAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing package.
     *
     * @param integer $id Id.
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function editAction($id, Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaPromotionBundle:Package')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'shop_package_admin');
        }

        $options =  array(
                      'action' => $this->generateUrl('shop_package_update_admin', array('id' => $entity->getId())),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm(ShopPackageAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit subscription'),
                      );

        return $this->render('FaPromotionBundle:ShopPackageAdmin:new.html.twig', $parameters);
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
        $entity      = $this->getRepository('FaPromotionBundle:Package')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find subscription.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'shop_package_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('shop_package_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(ShopPackageAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            $messageManager = $this->get('fa.message.manager');
            $messageManager->setFlashMessage('Subscription was successfully updated.', 'success');
            if ($form->get('saveAndNew')->isClicked()) {
                $redirectURL = $this->generateUrl('shop_package_new_admin');
            } else {
                if (empty($backUrl)) {
                    $backUrl = $this->generateUrl('shop_package_admin');
                }

                $redirectURL = $backUrl;
            }
            return $this->redirect($redirectURL);
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit subscription'),
                       );

        return $this->render('FaPromotionBundle:ShopPackageAdmin:new.html.twig', $parameters);
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
        CommonManager::setAdminBackUrl($request, $this->container);
        $backUrl = CommonManager::getAdminCancelUrl($this->container);

        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaPromotionBundle:Package')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find subscription.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'shop_package_admin');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage($this->get('translator')->trans('Subscription was successfully deleted.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
}
