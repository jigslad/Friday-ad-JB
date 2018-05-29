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

use Fa\Bundle\PromotionBundle\Entity\Upsell;
// use Fa\Bundle\PromotionBundle\Form\UpsellType;
// use Fa\Bundle\PromotionBundle\Form\UpsellSearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Form\UpsellSearchAdminType;
use Fa\Bundle\PromotionBundle\Form\ProfileUpsellAdminType;

/**
 * This controller is used for upsell management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version 1.0
 */
class ProfileUpsellAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return string
     */
    protected function getTableName()
    {
        return 'upsell';
    }

    /**
     * Lists all Entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPromotionBundle:Upsell'), $this->getRepositoryTable('FaPromotionBundle:Upsell'), 'fa_promotion_upsell_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('upsell' => array('id', 'type', 'title', 'price', 'status'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPromotionBundle:Upsell'), $data);
        $this->get('fa.sqlsearch.manager')->getQueryBuilder()->andWhere(UpsellRepository::ALIAS.'.upsell_for = :upsell');
        $this->get('fa.sqlsearch.manager')->getQueryBuilder()->setParameter('upsell', 'shop');
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UpsellSearchAdminType::class, null, array('action' => $this->generateUrl('profile_upsell_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray'      => EntityRepository::getStatusArray($this->container),
            'heading'          => $this->get('translator')->trans('Profile upsells'),
            'form'             => $form->createView(),
            'pagination'       => $pagination,
            'sorter'           => $data['sorter'],
        );

        return $this->render('FaPromotionBundle:ProfileUpsellAdmin:index.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new record.
     *
     * @return Response A response object.
     */
    public function newAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getEntity();
        $form        = $formManager->createForm(ProfileUpsellAdminType::class, $entity, array('action' => $this->generateUrl('profile_upsell_create_admin')));
        $parameters  = array(
                'entity'  => $entity,
                'form'    => $form->createView(),
                'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Creates a new record.
     *
     * @param Request $request A request object.
     *
     * @return Response A response object.
     */
    public function createAction(Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getEntity();
        $options     = array(
                'action' => $this->generateUrl('profile_upsell_create_admin'),
                'method' => 'POST'
        );

        $form = $formManager->createForm(ProfileUpsellAdminType::class, $entity, $options);
        if ($formManager->isValid($form)) {
            if (!$this->saveNewUsingForm) {
                $formManager->save($entity);
            }

            return parent::handleMessage($this->get('translator')->trans('Record has been added successfully.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'profile_upsell_new_admin' : 'profile_upsell_admin'));
        }

        $parameters = array(
                'entity'  => $entity,
                'form'    => $form->createView(),
                'heading' => $this->get('translator')->trans('New profile upsell'),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing record.
     *
     * @param integer $id Id.
     *
     * @return Response A response object.
     *
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                return parent::handleException($e, 'error', 'profile_upsell_admin');
        }

        $options =  array(
                'action' => $this->generateUrl('profile_upsell_update_admin', array('id' => $entity->getId())),
                'method' => 'PUT'
        );

        $form = $formManager->createForm(ProfileUpsellAdminType::class, $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
                'entity'  => $entity,
                'form'    => $form->createView(),
                'heading' => $this->get('translator')->trans('Edit profile upsell'),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Edits an existing record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl     = CommonManager::getAdminBackUrl($this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'profile_upsell_admin');
        }

        $options = array(
                'action' => $this->generateUrl('profile_upsell_update_admin', array('id' => $entity->getId())),
                'method' => 'PUT'
        );

        $form = $formManager->createForm(ProfileUpsellAdminType::class, $entity, $options);

        if ($formManager->isValid($form)) {
            if (!$this->saveEditUsingForm) {
                $this->getEntityManager()->flush();
            }

            $messageManager = $this->get('fa.message.manager');
            $messageManager->setFlashMessage('Record has been updated successfully.', 'success');
            if ($form->get('saveAndNew')->isClicked()) {
                $redirectURL = $this->generateUrl('profile_upsell_new_admin');
            } else {
                if(empty($backUrl))
                    $backUrl = $this->generateUrl('profile_upsell_admin');
                $redirectURL = $backUrl;
            }
            return $this->redirect($redirectURL);
        }

        $parameters = array(
                'entity'  => $entity,
                'form'    => $form->createView(),
                'heading' => $this->get('translator')->trans('Edit profile upsell'),
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }
}
