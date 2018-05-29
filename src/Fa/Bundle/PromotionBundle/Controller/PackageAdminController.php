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

use Fa\Bundle\PromotionBundle\Entity\Package;
// use Fa\Bundle\PromotionBundle\Form\PackageType;
// use Fa\Bundle\PromotionBundle\Form\PackageSearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Form\PackageSearchAdminType;
use Fa\Bundle\PromotionBundle\Form\PackageAdminType;

/**
 * This controller is used for package management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PackageAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all Entities.
     *
     * @return Response A Response object.
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPromotionBundle:Package'), $this->getRepositoryTable('FaPromotionBundle:Package'), 'fa_promotion_package_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('package' => array('id', 'title', 'price', 'admin_price', 'status', 'is_admin_package'));

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
        $query->getQueryBuilder()->setParameter('package', 'ad');
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
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(PackageSearchAdminType::class, null, array('action' => $this->generateUrl('package_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('Packages'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'categoryLocationArray' => $categoryLocationArray,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaPromotionBundle:PackageAdmin:index.html.twig', $parameters);
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

        $entity = new Package();

        $options =  array(
                      'action' => $this->generateUrl('package_create_admin'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(PackageAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            return parent::handleMessage($this->get('translator')->trans('Package was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'package_new_admin' : ($backUrl ? $backUrl : 'package_admin')));

        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New package'),
                      );

        return $this->render('FaPromotionBundle:PackageAdmin:new.html.twig', $parameters);
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

        $entity = new Package();

        $form = $formManager->createForm(PackageAdminType::class, $entity, array('action' => $this->generateUrl('package_create_admin')));

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New package'),
                      );

        return $this->render('FaPromotionBundle:PackageAdmin:new.html.twig', $parameters);
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

        $entity = $this->getRepository('FaPromotionBundle:Package')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'package_admin');
        }

        $options =  array(
                      'action' => $this->generateUrl('package_update_admin', array('id' => $entity->getId())),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm(PackageAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit package'),
                      );

        return $this->render('FaPromotionBundle:PackageAdmin:new.html.twig', $parameters);
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
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'package_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('package_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(PackageAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            return parent::handleMessage($this->get('translator')->trans('Package was successfully updated.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'package_new_admin' : ($backUrl ? $backUrl : 'package_admin')));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit package'),
                       );

        return $this->render('FaPromotionBundle:PackageAdmin:new.html.twig', $parameters);
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
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaPromotionBundle:Package')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find package.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'package_admin');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage($this->get('translator')->trans('Package was successfully deleted.', array(), 'success'), ($backUrl ? $backUrl : 'package_admin'));
    }
}
