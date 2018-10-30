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

use Fa\Bundle\AdBundle\Entity\Ad;
// use Fa\Bundle\AdBundle\Form\AdType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Doctrine\ORM\Query;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdUserPackageRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentTransactionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\AdBundle\Form\AdSearchAdminType;
use Fa\Bundle\AdBundle\Form\ChangeStatusAdminType;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdAdminController extends CrudController implements ResourceAuthorizationController
{

    /**
     * Get table name.
     *
     */
    protected function getTableName()
    {
        return 'ad';
    }

    /**
     * Lists all Ad entities.
     *
     * @param Request $request
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        //CommonManager::setAdminBackUrl($request, $this->container);
        $pagination = null;

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'fa_ad_ad_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $data['select_fields'] = array('ad' => array('id', 'price', 'created_at', 'future_publish_at','source'));

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(AdSearchAdminType::class, null, array('action' => $this->generateUrl('ad_admin'), 'method' => 'GET'));

        // handle joins
        $data = $this->handleJoins($data);

        $data = $this->handleRole($data);

        if ($data['search']) {
            if (isset($data['search']['ad__source'])) {
                if ($data['search']['ad__source'][0]==1) {
                    $data['static_filters'] = AdRepository::ALIAS.".source = 'paa_lite'";
                }
            }
            
            $form->submit($data['search']);
        }

        if ($form->isValid()) {
            $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:Ad'), $data);
            $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
            $queryBuilder->distinct(AdRepository::ALIAS.'.id');

            //$query = $this->handleIndex($data, $queryBuilder->getQuery());
            $query = $queryBuilder->getQuery();

            // initialize pagination manager service and prepare listing with pagination based of data
            $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
            $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
            $pagination = $this->get('fa.pagination.manager')->getPagination();
        }


        $parameters = array(
            'heading'     => 'Ads',
            'form'        => $form->createView(),
            'pagination'  => $pagination,
            'sorter'      => $data['sorter'],
        );

        return $this->render('FaAdBundle:AdAdmin:index.html.twig', $parameters);
    }

    /**
     * Use inner join when search from user table.
     *
     * @param String $data
     *
     * @return Response A Response object.
     */
    public function handleJoins($data)
    {
        if ((isset($data['search']['user__email']) && $data['search']['user__email']) ||
        (isset($data['search']['user__first_name']) && $data['search']['user__first_name']) ||
        (isset($data['search']['user__phone']) && $data['search']['user__phone']) ||
        (isset($data['search']['user__paypal_email']) && $data['search']['user__paypal_email']) ||
        (isset($data['search']['user__role']) && $data['search']['user__role']) ||
        (isset($data['search']['user__status']) && $data['search']['user__status'])
        ) {
            $data['query_joins']['ad']['user'] = array('type' => 'inner');
        }

        if (isset($data['search']['ad_user_package__package']) && $data['search']['ad_user_package__package']) {
            $data['query_joins']['ad']['ad_user_package'] = array('type' => 'inner', 'condition_type' => 'WITH', 'condition' => AdRepository::ALIAS.'.id = '.AdUserPackageRepository::ALIAS.'.ad_id');
        }

        if (isset($data['search']['payment_transaction__payment__cart_code']) && $data['search']['payment_transaction__payment__cart_code']) {
            $data['query_joins']['ad']['payment_transaction'] = array('type' => 'inner');
            $data['query_joins']['payment_transaction']['payment'] = array('type' => 'inner');
        }

        return $data;
    }

    /**
     * Changes status of ad.
     *
     * @param integer $id
     * @param Request $request
     *
     * @return Response A Response object.
     */
    public function changeStatusAction($id, Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize form manager service
        $formData    = $request->get('fa_ad_ad_change_status_admin');
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaAdBundle:Ad')->find($id);
        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_admin');
        }

        if ($entity && $entity->getUser() && $entity->getUser()->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
            $urlToRedirect = CommonManager::getAdminCancelUrl($this->container);
            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('You can not change ad status, as user is not active!'), 'error');
            return $this->redirect($urlToRedirect);
        }

        $options =  array(
            'action' => $this->generateUrl('ad_change_status', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(ChangeStatusAdminType::class, $entity, $options);

        $previous_status = '';
        $previous_id     = null;
        if ($entity->getStatus()) {
            $previous_status = $entity->getStatus()->getName();
            $previous_id     = $entity->getStatus()->getId();
        }

        if ($formManager->isValid($form)) {
            if (in_array($previous_id, array(EntityRepository::AD_STATUS_IN_MODERATION_ID, EntityRepository::AD_STATUS_REJECTED_ID, EntityRepository::AD_STATUS_REJECTEDWITHREASON_ID, EntityRepository::AD_STATUS_INACTIVE_ID)) && $formData['ad_status'] == EntityRepository::AD_STATUS_LIVE_ID) {
                try {
                    $this->getEntityManager()->beginTransaction();
                    $this->getRepository('FaAdBundle:AdModerate')->applyModerationOnLiveAd($entity->getId(), $this->container);
                    $expirationDays = $this->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($entity->getCategory()->getId(), $this->container);
                    $entity->setExpiresAt($this->getRepository('FaAdBundle:Ad')->getAdPrintExpiry($entity->getId(), CommonManager::getTimeFromDuration($expirationDays.'d')));
                    $this->getEntityManager()->getConnection()->commit();
                } catch (\Exception $e) {
                    $this->getEntityManager()->getConnection()->rollback();
                    $this->handleException($e);
                }
            } else {
                $currentTime = time();
                if ($formData['ad_status'] == EntityRepository::AD_STATUS_SOLD_ID) {
                    $entity->setSoldAt($currentTime);
                } elseif ($formData['ad_status'] == EntityRepository::AD_STATUS_EXPIRED_ID) {
                    $entity->setExpiresAt($currentTime);
                    // insert expire stat
                    $this->getRepository('FaAdBundle:AdStatistics')->insertExpiredStat($entity, $currentTime);
                }
                $formManager->save($entity);

                if (in_array($formData['ad_status'], array(EntityRepository::AD_STATUS_EXPIRED_ID, EntityRepository::AD_STATUS_INACTIVE_ID, EntityRepository::AD_STATUS_SOLD_ID))) {
                    // inactivate the package
                    $this->getRepository('FaAdBundle:Ad')->doAfterAdCloseProcess($entity->getId(), $this->container);
                }
            }

            //update edited ad
            $entity->setEditedAt(time());
            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush($entity);
            $this->getRepository('FaAdBundle:AdIpAddress')->checkAndLogIpAddress($entity, $request->getClientIp());

            if (isset($formData['return_url']) && $formData['return_url']) {
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Ad status has been changed successfully.'), 'success');
                return $this->redirect($formData['return_url']);
            }

            return $this->handleMessage($this->get('translator')->trans('Ad status has been changed successfully.'), 'ad_admin');
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => 'Change Ad Status',
        );

        return $this->render('FaAdBundle:AdAdmin:changeStatus.html.twig', $parameters);
    }

    /**
     * To get the performance special case required for role.
     *
     * @param object $data.
     *
     * @return Response A Response object.
     */
    public function handleRole($data)
    {
        if (isset($data['search']['user__role__id']) && $data['search']['user__role__id'] && $data['search']['user__role__id'] == RoleRepository::ROLE_USER_ID) {
            $data['query_filters']['user']['role'] = $data['query_filters']['user__role']['id'];
            unset($data['query_filters']['user__role']);
        }

        return $data;
    }

    /**
     * Manage index for performance.
     *
     * @param string $data
     * @param mixed  $query
     *
     * @return Query.
     */
    public function handleIndex($data, $query)
    {
        if ((isset($data['search']['user__email']) && $data['search']['user__email']) ||
        (isset($data['search']['user__customer_name']) && $data['search']['user__customer_name']) ||
        (isset($data['search']['user__phone']) && $data['search']['user__phone']) ||
        (isset($data['search']['user__paypal_email']) && $data['search']['user__paypal_email'])
        ) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\AdBundle\Walker\AdAdminSearchSqlWalker');
            $query->setHint("adAdminSearchSqlWalker.userIndex", true);
        }

        if (isset($data['search']['category__name']) && $data['search']['category__name']) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\AdBundle\Walker\AdAdminSearchSqlWalker');
            $query->setHint("adAdminSearchSqlWalker.categoryIndex", true);
        }

        if ((isset($data['search']['ad_locations__location_domicile__name']) && $data['search']['ad_locations__location_domicile__name']) ||
        (isset($data['search']['ad_locations__location_town__name']) && $data['search']['ad_locations__location_town__name'])
        ) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\AdBundle\Walker\AdAdminSearchSqlWalker');
            $query->setHint("adAdminSearchSqlWalker.locationIndex", true);
        }

        return $query;
    }

    /**
     * Deletes a record.
     *
     * @see \Fa\Bundle\AdminBundle\Controller\CrudController::deleteAction()
     *
     * @param Request $request
     * @param integer $id
     *
     * @throws createNotFoundException
     * @return Response A Response object.
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl       = CommonManager::getAdminBackUrl($this->container);
        $deleteManager = $this->get('fa.deletemanager');
        $entity        = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        try {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();

            $solrClient = $this->container->get('fa.solr.client.ad');
            if (!$solrClient->ping()) {
                return false;
            }

            $solr = $solrClient->connect();
            $solr->deleteById($id);
            $solr->commit();
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), $this->getRouteName(''), array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', $this->getRouteName(''));
        }

        if ($request->get('return_url')) {
            $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Record has been deleted successfully.'), 'success');
            return $this->redirect($request->get('return_url'));
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }

    /**
     * Ad detail.
     *
     * @param integer $id
     * @param Request $request
     *
     * @throws createNotFoundException
     * @return Response A Response object.
     */
    public function adDetailAction($id, Request $request)
    {
        $ad = $this->getRepository('FaAdBundle:Ad')->find($id);

        try {
            if (!$ad) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find Ad.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'ad_admin');
        }

        $adDetail = $this->getRepository('FaAdBundle:Ad')->getAdDetailArray($id, $this->container);

        //remove script tag from description
        if (isset($adDetail['description'])) {
            $adDetail['description'] = CommonManager::stripTagsContent(htmlspecialchars_decode($adDetail['description']), '<em><strong><b><i><u><p><ul><li><ol><div><span><br>');
        }

        $parameters = array(
            'ad' => $ad,
            'adDetail' => $adDetail,
        );

        return $this->render('FaAdBundle:AdAdmin:adDetail.html.twig', $parameters);
    }

    /**
     * Ad report list csv action.
     *
     * @param integer $id
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxListAdPrintInsertDatesAction($id, Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $htmlContent = $this->renderView('FaAdBundle:AdAdmin:ajaxListAdPrintInsertDates.html.twig', array('id' => $id));
            return new JsonResponse(array('htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }
}
