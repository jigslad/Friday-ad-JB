<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Controller\CoreController;

use Fa\Bundle\UserBundle\Entity\User;
// use Fa\Bundle\UserBundle\Form\UserType;
use Fa\Bundle\UserBundle\Form\UserSearchType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Doctrine\ORM\Query;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserPackageRepository;
use Fa\Bundle\UserBundle\Form\UserAdSearchType;
use Fa\Bundle\UserBundle\Form\UserPaymentSearchType;
use Fa\Bundle\UserBundle\Form\UserAdminType;
use Fa\Bundle\UserBundle\Form\ChangeStatusType;
use Fa\Bundle\UserBundle\Form\BoostOverideType;

/**
 * This controller is used for user management.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all User entities.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        $this->container->get('session')->set('go_back_url', $request->getUri());

        //Back to user list;
        $currentUrl = $request->getUri();
        $session = $this->container->get('session');
        $session->set('admin_backto_userlist_url', $currentUrl);

        $fetchJoinCollection = true;
        $pagination = null;

        // When user searched from ad posting page then it will be redired here
        $adUserSearchData = $request->get('fa_ad_user_search_admin', array());
        if (is_array($adUserSearchData) && count($adUserSearchData)) {
            $request->attributes->set('fa_user_user_search_admin', $adUserSearchData);
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:User'), $this->getRepositoryTable('FaUserBundle:User'), 'fa_user_user_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // Search ony full account users
        $data['query_filters']['user']['is_half_account'] = 0;

        if (isset($data['query_filters']['ad__category']) && isset($data['query_filters']['ad__category']['id_json'])) {
            unset($data['query_filters']['ad__category']['id_json']);
        }

        $data['select_fields'] = array(
                                        'user' => array('id')
                                    );

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserSearchType::class, null, array('action' => $this->generateUrl('user_admin'), 'method' => 'GET'));

        // handle dates
        $data = $this->handleDates($data);

        // handle joins
        $data = $this->handleJoins($data);

        // handle role for performance
        $data = $this->handleRole($data);

        if ($data['search']) {
            $form->submit($data['search']);
        }

        if ($form->isValid()) {
            $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:User'), $data);
            $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
            $queryBuilder->distinct(UserRepository::ALIAS.'.id');

            // handle creation date of the most recent advert & expiry date of the most recent advert
            if ((isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'ad__created_at') ||
            (isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'ad__expires_at')
            ) {
                //$queryBuilder->addGroupBy(UserRepository::ALIAS.'.id');
                $fetchJoinCollection = false;
            }

            // handle index for performance
            //$query = $this->handleIndex($data, $queryBuilder->getQuery());

            if (isset($data['query_filters']['user__user_package']) && !empty($data['query_filters']['user__user_package']['package_id'])) {
                $queryBuilder->andWhere(UserPackageRepository::ALIAS.'.status = :status');
                $queryBuilder->setParameter('status', 'A');
            }

            $query = $queryBuilder->getQuery();

            // initialize pagination manager service and prepare listing with pagination based of data
            $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
            $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
            $pagination = $this->get('fa.pagination.manager')->getPagination($fetchJoinCollection);
        }

        $parameters = array(
            'heading'     => 'Users',
            'form'        => $form->createView(),
            'pagination'  => $pagination,
            'sorter'      => $data['sorter'],
        );

        return $this->render('FaUserBundle:UserAdmin:index.html.twig', $parameters);
    }

    /**
     * To get the performance special case required for role.
     *
     * @param array $data Data array.
     *
     * @return array
     */
    public function handleRole(array $data)
    {
        if (isset($data['search']['role__id']) && $data['search']['role__id'] && ($data['search']['role__id'] == RoleRepository::ROLE_SELLER_ID || $data['search']['role__id'] == RoleRepository::ROLE_BUSINESS_SELLER_ID || $data['search']['role__id'] == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID)) {
            $data['query_filters']['user']['role'] = $data['query_filters']['role']['id'];
            unset($data['query_filters']['role']);
        }

        return $data;
    }

    /**
     * Handle date range.
     *
     * @param array $data Data array.
     *
     * @return array
     */
    public function handleDates(array $data)
    {
        if (isset($data['search']['ad__created_at_from']) && $data['search']['ad__created_at_from'] && isset($data['search']['ad__created_at_to']) && !$data['search']['ad__created_at_to']) {
            $data['ad__created_at_to'] = date('d\m\Y', time());
        } elseif (isset($data['search']['ad__created_at_to']) && $data['search']['ad__created_at_to'] && isset($data['search']['ad__created_at_from']) && !$data['search']['ad__created_at_from']) {
            $data['ad__created_at_from'] = date('d\m\y', strtotime("-2 month"));
        }

        return $data;
    }

    /**
     * Use inner join when search from ad table.
     *
     * @param array $data Data array.
     *
     * @return array
     */
    public function handleJoins(array $data)
    {
        if ((isset($data['search']['ad__id']) && $data['search']['ad__id']) ||
        (isset($data['search']['ad__ti_ad_id']) && $data['search']['ad__ti_ad_id']) ||
        (isset($data['search']['ad__title']) && $data['search']['ad__title']) ||
        (isset($data['search']['ad__created_at_from']) && $data['search']['ad__created_at_from']) ||
        (isset($data['search']['ad__created_at_to']) && $data['search']['ad__created_at_to']) ||
        (isset($data['search']['ad__category__id']) && $data['search']['ad__category__id']) ||
        (isset($data['search']['ad__entity_ad_type__id']) && $data['search']['ad__entity_ad_type__id']) ||
        (isset($data['search']['ad__entity_ad_status__id']) && $data['search']['ad__entity_ad_status__id']) ||
        (isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'ad__created_at') ||
        (isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'ad__expires_at')
        ) {
            $data['query_joins']['user']['ad'] = array('type' => 'inner', 'condition_type' => 'WITH');
        }

        if ((isset($data['search']['ad__category__id']) && $data['search']['ad__category__id'])) {
            $data['query_joins']['ad']['category'] = array('type' => 'inner');
        }

        if ((isset($data['search']['ad__entity_ad_type__id']) && $data['search']['ad__entity_ad_type__id'])) {
            $data['query_joins']['ad']['entity_ad_type'] = array('type' => 'inner');
        }

        if ((isset($data['search']['ad__entity_ad_status__id']) && $data['search']['ad__entity_ad_status__id'])) {
            $data['query_joins']['ad']['entity_ad_status'] = array('type' => 'inner');
        }

        if ((isset($data['search']['user__user_package__package_id']) && $data['search']['user__user_package__package_id'])) {
            $data['query_joins']['user']['user_package'] = array('type' => 'inner');
        }

        return $data;
    }

    /**
     * Manage index for performance.
     *
     * @param array  $data   Data array.
     * @param object $object Doctrine query object.
     *
     * @return object Doctrine query object.
     */
    public function handleIndex(array $data, $query)
    {
        if ((isset($data['search']['user__email']) && $data['search']['user__email']) ||
        (isset($data['search']['user__customer_name']) && $data['search']['user__customer_name']) ||
        (isset($data['search']['user__phone']) && $data['search']['user__phone']) ||
        (isset($data['search']['user__paypal_email']) && $data['search']['user__paypal_email'])
        ) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\UserBundle\Walker\UserAdminSearchSqlWalker');
            $query->setHint("userAdminSearchSqlWalker.userIndex", true);
        }

        if ((isset($data['search']['ad__id']) && $data['search']['ad__id']) ||
        (isset($data['search']['ad__title']) && $data['search']['ad__title']) ||
        (isset($data['search']['ad__created_at_from']) && $data['search']['ad__created_at_from']) ||
        (isset($data['search']['ad__created_at_to']) && $data['search']['ad__created_at_to'])
        ) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\UserBundle\Walker\UserAdminSearchSqlWalker');
            $query->setHint("userAdminSearchSqlWalker.adIndex", true);
        }

        if ((isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'ad__created_at') ||
        (isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'ad__expires_at')
        ) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\UserBundle\Walker\UserAdminSearchSqlWalker');
            $query->setHint("userAdminSearchSqlWalker.adIndex", true);
        }

        if ((isset($data['sorter']['sort_field']) && $data['sorter']['sort_field'] == 'user_statistics__total_ad')) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\UserBundle\Walker\UserAdminSearchSqlWalker');
            $query->setHint("userAdminSearchSqlWalker.userStatisticsIndex", true);
        }

        return $query;
    }

    /**
     * Creates a new User entity.
     *
     * @param Request $request A Request object.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function createAction(Request $request)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager          = $this->get('fa.formmanager');
        $isShowBusinessSeller = false;

        $formData = $request->get('fa_user_user_admin');
        if ($formData['email']) {
            // check if email address is of half account then, update that account, no need to do new entry.
            $halfAccount = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $formData['email'], 'is_half_account' => 1));
            if ($halfAccount) {
                $entity = $halfAccount;
            } else {
                $entity = new User();
            }
        } else {
            $entity = new User();
        }

        $options =  array(
                      'action' => $this->generateUrl('user_create_admin'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(UserAdminType::class, $entity, $options);

        if ($formManager->isValid($form)) {
            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                //encode the password
                $encoder = $this->container->get('security.encoder_factory')->getEncoder($entity); //get encoder for hashing pwd later
                $password = $encoder->encodePassword($entity->getPassword(), $entity->getSalt());
                $entity->setPassword($password);
            }

            // Set full account
            $entity->setIsHalfAccount(0);

            $formManager->save($entity);

            $routeParams = array();
            $successMsg  = 'User was created successfully.';
            if ($form->get('save')->isClicked()) {
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($successMsg, 'success');
                $redirectURL = $this->generateUrl('user_admin').'?fa_user_user_search_admin[user__id]='.$entity->getId();
                return $this->redirect($redirectURL);
            } elseif ($form->get('saveAndNew')->isClicked()) {
                $redirectRoute = 'user_new_admin';
            } elseif ($form->get('saveAndCreateAd')->isClicked()) {
                $routeParams['user_id'] = $entity->getId();
                $redirectURL = $this->generateUrl('ad_post_new_admin', $routeParams);
                return $this->redirect($redirectURL);
            } elseif (isset($backUrl)) {
                $redirectRoute = $backUrl;
            }

            return $this->handleMessage($this->get('translator')->trans($successMsg), $redirectRoute, $routeParams);
        }

        $role = $form->getData()->getRoles();
        if ($role != '') {
            if ($role == RoleRepository::ROLE_BUSINESS_SELLER_ID || $role == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION_ID) {
                $isShowBusinessSeller = true;
            }
        }

        $parameters = array(
                        'entity'               => $entity,
                        'form'                 => $form->createView(),
                        'heading'              => 'New user',
                        'isShowBusinessSeller' => $isShowBusinessSeller,
                      );

        return $this->render('FaUserBundle:UserAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new User entity.
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

        $entity = new User();

        $form = $formManager->createForm(UserAdminType::class, $entity, array('action' => $this->generateUrl('user_create_admin')));

        $parameters = array(
                        'entity'               => $entity,
                        'form'                 => $form->createView(),
                        'heading'              => 'New user',
                        'isShowBusinessSeller' => false,
                      );

        return $this->render('FaUserBundle:UserAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @param integer $id Id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager          = $this->get('fa.formmanager');
        $isShowBusinessSeller = false;

        $entity = $this->getRepository('FaUserBundle:User')->find($id);

        try {
            if (!$entity || $entity->getIsHalfAccount() == 1) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $routeParams = array('id' => $entity->getId());
        if ($request->get('from', null) == 'user_show') {
            $routeParams['from'] = 'user_show';
        }

        $options =  array(
                      'action' => $this->generateUrl('user_update_admin', $routeParams),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm(UserAdminType::class, $entity, $options);

        $roles = $entity->getRoles();
        foreach ($roles as $role) {
            if ($role->getName() == RoleRepository::ROLE_BUSINESS_SELLER || $role->getName() == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                $isShowBusinessSeller = true;
            }
        }

        $parameters = array(
                        'entity'               => $entity,
                        'form'                 => $form->createView(),
                        'heading'              => 'Edit user',
                        'isShowBusinessSeller' => $isShowBusinessSeller,
                      );

        return $this->render('FaUserBundle:UserAdmin:new.html.twig', $parameters);
    }

    /**
     * Edits an existing User entity.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager          = $this->get('fa.formmanager');
        $isShowBusinessSeller = false;

        $entity           = $this->getRepository('FaUserBundle:User')->find($id);
        $originalPassword = $entity->getPassword();

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $oldEmail                = $entity->getEmail();
        $oldIsPrivatePhoneNumber = $entity->getIsPrivatePhoneNumber();
        $oldPhoneNumber          = $entity->getPhone();
        $oldPassword             = $entity->getPassword();

        $options =  array(
            'action' => $this->generateUrl('user_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(UserAdminType::class, $entity, $options);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            $plainPassword = $form->get('password')->getData();
            if (!empty($plainPassword)) {
                //encode the password
                $encoder = $this->container->get('security.encoder_factory')->getEncoder($entity); //get encoder for hashing pwd later
                $password = $encoder->encodePassword($entity->getPassword(), $entity->getSalt());
                //$entity->setPassword($password);
                $connection = $this->getEntityManager()->getConnection();
                $statement = $connection->prepare("Update user set password = :password Where id = :id");
                $statement->bindValue('password', $password);
                $statement->bindValue('id', $entity->getId());
                $statement->execute();
            } else {
                //$entity->setPassword($originalPassword);
                $connection = $this->getEntityManager()->getConnection();
                $statement = $connection->prepare("Update user set password = :password Where id = :id");
                $statement->bindValue('password', $originalPassword);
                $statement->bindValue('id', $entity->getId());
                $statement->execute();
            }

            // updating email and social media connections.
            if ($oldEmail != $form->get('email')->getData()) {
                $entity->setEmail($form->get('email')->getData());
                $entity->setUserName($form->get('email')->getData());
                $entity->setFacebookId(null);
                $entity->setGoogleId(null);
                $entity->setIsFacebookVerified(0);
            }

            $em = $this->getEntityManager();
            $em->persist($entity);
            $em->flush();

            // update yac number if phone number changes and user has set privacy number.
            if ($form->get('is_private_phone_number')->getData() && $oldPhoneNumber != $form->get('phone')->getData()) {
                //exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:user-ad-yac-number edit --user_id='.$id.' >/dev/null &');
                //commented FFR-3756 
            }
 
            // update yac number if privacy phone number setting is changes.
            if ($oldIsPrivatePhoneNumber != $form->get('is_private_phone_number')->getData()) {
                if ($form->get('is_private_phone_number')->getData()) {
                    //exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:user-ad-yac-number allocate --user_id='.$id.' >/dev/null &');
                    //commented FFR-3756
                } elseif (!$form->get('is_private_phone_number')->getData()) {
                    //exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:user-ad-yac-number setsold --user_id='.$id.' >/dev/null &');
                    //commented FFR-3756 
                }
            }

            $routeParams = array();
            $successMsg  = 'User was updated successfully.';
            if ($form->get('save')->isClicked()) {
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($successMsg, 'success');
                if (empty($backUrl)) {
                    $backUrl = $this->generateUrl('user_admin').'?fa_user_user_search_admin[user__id]='.$entity->getId();
                }
                return $this->redirect($backUrl);
            } elseif ($form->get('saveAndNew')->isClicked()) {
                $redirectRoute = 'user_new_admin';
            } elseif ($form->get('saveAndCreateAd')->isClicked()) {
                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($successMsg, 'success');
                $routeParams['user_id'] = $entity->getId();
                $redirectURL = $this->generateUrl('ad_post_new_admin', $routeParams);
                return $this->redirect($redirectURL);
            } elseif (isset($backUrl)) {
                $redirectRoute = $backUrl;
            }

            return $this->handleMessage($this->get('translator')->trans($successMsg), $redirectRoute, $routeParams);
        } else {
            $entity->setPassword($oldPassword);
        }

        $roles = $form->getData()->getRoles();
        foreach ($roles as $role) {
            if ($role->getName() == RoleRepository::ROLE_BUSINESS_SELLER || $role->getName() == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                $isShowBusinessSeller = true;
            }
        }

        $parameters = array(
                        'entity'               => $entity,
                        'form'                 => $form->createView(),
                        'heading'              => 'Edit user',
                        'isShowBusinessSeller' => $isShowBusinessSeller,
                       );

        return $this->render('FaUserBundle:UserAdmin:new.html.twig', $parameters);
    }

    /**
     * Deletes a User entity.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse A RedirectResponse object.
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaUserBundle:User')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        //update cart value and payment method.
        $this->getEntityManager()->beginTransaction();
        try {
            $this->getRepository('FaMessageBundle:Message')->removeMessageByUserId($entity->getId(), $this->getEntityManager());
            $deleteManager->delete($entity);
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->getConnection()->rollback();
            return $this->handleException($e, 'error', 'user_admin');
        }

        $this->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($id, $this->container);

        return $this->handleMessage($this->get('translator')->trans('User was deleted successfully.'), ($backUrl ? $backUrl : 'user_admin'));
    }

    /**
     * Displays User details.
     *
     * @param integer $id Id.
     *
     * @return Response A Response object.
     */
    public function showAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $user = $this->getRepository('FaUserBundle:User')->find($id);

        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $parameters = array(
            'user'    => $user,
            'heading' => $this->get('translator')->trans('User detail'),
        );

        return $this->render('FaUserBundle:UserAdmin:show.html.twig', $parameters);
    }

    /**
     * Lists all User ads.
     *
     * @param integer $id Id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function showUserAdAction($id)
    {
        $user = $this->getRepository('FaUserBundle:User')->find($id);
        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'fa_user_user_ad_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['query_joins']   = array('ad' => array('user' => array('type' => 'inner')));
        $data['select_fields'] = array('ad' => array('id', 'title', 'created_at', 'future_publish_at'));
        $data['static_filters'] = AdRepository::ALIAS.'.user = '.$id;

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:Ad'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserAdSearchType::class, null, array('action' => $this->generateUrl('user_ad_list_admin', array('id' => $id)), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'user'    => $user,
            'heading' => $this->get('translator')->trans('User detail'),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
            'form'        => $form->createView(),
        );

        return $this->render('FaUserBundle:UserAdmin:show.html.twig', $parameters);
    }

    /**
     * Changes status of User entity.
     *
     * @param integer $id      Id.
     * @param Request $request A Request object.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function changeStatusAction($id, Request $request)
    {
        if (!empty($id) && strpos($id, ',') == true) {
            $userIds = explode(',', $id);
        } elseif (!is_array($id)) {
            $userIds = array($id);
        }

        $goBackUrl = null;
        $session = $this->container->get('session');
        if ($session->has('go_back_url')) {
            $goBackUrl = $session->get('go_back_url');
        } else {
            $goBackUrl = CommonManager::getAdminBackUrl($this->container);
            $session->set('go_back_url', $goBackUrl);
        }

        if ($request->getMethod() != 'PUT') {
            CommonManager::setAdminBackUrl($request, $this->container);
        }

        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entities = $this->getRepository('FaUserBundle:User')->findBy(array('id' => $userIds));

        try {
            if (!$entities) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('user_change_status', array('id' => $id)),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(ChangeStatusType::class, $entities[0], $options);

        $previous_status = '';
        /*
        if ($entity->getStatus()) {
            $previous_status = $entity->getStatus()->getName();
        }*/

        if ($formManager->isValid($form)) {
            try {
                foreach ($entities as $entity) {
                    $this->getEntityManager()->beginTransaction();
                    //$entity$formManager->save($entity);
                    $userStatus = $form->get('user_status')->getData();
                    $entity->setStatus($this->getEntityManager()->getReference('FaEntityBundle:Entity', $userStatus));
                    $this->getEntityManager()->persist($entity);

                    if ($entity->getStatus() && $entity->getStatus()->getId() != EntityRepository::USER_STATUS_ACTIVE_ID) {
                        // inactive ad
                        $this->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($entity->getId(), 1);
                        $this->getRepository('FaAdBundle:Ad')->deleteAdFromSolrByUserId($entity->getId(), $this->container);
                    } else {
                        $this->getRepository('FaAdBundle:Ad')->blockUnblockAdByUserId($entity->getId(), 0);
                        $this->getRepository('FaAdBundle:Ad')->updateAdFromSolrByUserId($entity->getId(), $this->container);
                    }
                    //update solr indexing for A,S,E ads in background if user status is active.
                    /*if ($entity->getStatus() && $entity->getStatus()->getId() == EntityRepository::USER_STATUS_ACTIVE_ID) {
                        $command = $this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-solr-index update --user_id="'.$entity->getId().'" --status="A,S,E"';
                        passthru($command, $returnVar);
                        //exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:ad-solr-index add --user_id="'.$entity->getId().'" --status="A,S,E" >/dev/null &');
                    }*/
                    $this->getEntityManager()->getConnection()->commit();
                }

                $this->getEntityManager()->flush();
                //$messageManager = $this->get('fa.message.manager');
                //$messageManager->setFlashMessage("User status has been changed successfully", 'success');
                return $this->handleMessage($this->get('translator')->trans('User status has been changed successfully.'), ($goBackUrl ? $goBackUrl : 'user_admin'));
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in Changing user status', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Sorry there is an issue in updating user status.'), ($backUrl ? $backUrl : 'user_admin'));
            }

            try {
                $this->get('fa.mail.manager')->send($entity->getEmail(), 'user_status_change', array('customer_name' => ucwords($entity->getFirstName().' '.$entity->getLastName()), 'current_status' => $entity->getStatus()->getName(), 'previous_status' => $previous_status), 'en_GB');
            } catch (\Exception $e) {
            }
        }

        $parameters = array(
            'entities' => $entities,
            'userIds'  => $id,
            'form'     => $form->createView(),
            'heading'  => 'Change user status',
            'goBackUrl' => $goBackUrl
        );

        return $this->render('FaUserBundle:UserAdmin:changeStatus.html.twig', $parameters);
    }

    /**
     * Lists all user reviews.
     *
     * @param integer $id Id.
     *
     * @return Response A Response object.
     */
    public function showUserReviewsAction($id)
    {
        $user = $this->getRepository('FaUserBundle:User')->find($id);
        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $reviews = $this->getRepository('FaUserBundle:UserReview')->getReviewsByUserId($id);

        $parameters = array(
            'user'    => $user,
            'reviews' => $reviews,
            'heading' => $this->get('translator')->trans('User Reviews'),
        );

        return $this->render('FaUserBundle:UserAdmin:show.html.twig', $parameters);
    }

    /**
     * Lists all user reviews.
     *
     * @param integer $id Id.
     *
     * @return Response A Response object.
     */
    public function showUserReviewsLeftForOtherAction($id)
    {
        $user = $this->getRepository('FaUserBundle:User')->find($id);
        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $reviews = $this->getRepository('FaUserBundle:UserReview')->getReviewsByReviewerId($id);

        $parameters = array(
            'user'           => $user,
            'reviews'        => $reviews,
            'heading'        => $this->get('translator')->trans('User Reviews Left for Others'),
            'isLeftForOther' => 1
        );

        return $this->render('FaUserBundle:UserAdmin:show.html.twig', $parameters);
    }

    /**
     * Uer review edit.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function userReviewEditAjaxAction(Request $request)
    {
        $statusArray = $this->getRepository('FaUserBundle:UserReview')->getStatusArray($this->container);

        $reviewId = str_replace('review_', '', $request->get('id'));
        $value    = $request->get('value');

        $review = $this->getRepository('FaUserBundle:UserReview')->find($reviewId);

        if (!$review) {
            throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user review entity.'));
        }

        if (!$request->isXmlHttpRequest()) {
            return $this->redirect($this->generateUrl('user_reviews_list_admin', array('id' => $review->getUser()->getId())));
        }

        if ($request->get('field') == 'message') {
            $review->setMessage($value);
        } elseif ($request->get('field') == 'report') {
            $review->setReport($value);
        } elseif ($request->get('field') == 'status' && isset($statusArray[$value])) {
            $review->setStatus($value);
        }

        $this->getEntityManager()->persist($review);
        $this->getEntityManager()->flush();

        $message = null;
        if ($request->get('field') == 'message') {
            $message = $review->getMessage();
        } elseif ($request->get('field') == 'report') {
            $message = $review->getReport();
        } elseif ($request->get('field') == 'status' && isset($statusArray[$review->getStatus()])) {
            $message = $statusArray[$review->getStatus()];
        }

        return new Response($message);
    }

    /**
     * Uer review delete.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @throws NotFoundHttpException
     * @return RedirectResponse A RedirectResponse object.
     */
    public function userReviewDeleteAction(Request $request, $id)
    {
        $deleteManager = $this->get('fa.deletemanager');
        $review        = $this->getRepository('FaUserBundle:UserReview')->find($id);

        try {
            if (!$review) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find user review entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_reviews_list_admin', array('id' => $review->getUser()->getId()));
        }

        $deleteManager->delete($review);

        return $this->handleMessage($this->get('translator')->trans('User review was deleted successfully.'), 'user_reviews_list_admin', array('id' => $review->getUser()->getId()));
    }

    /**
     * Lists payment of user.
     *
     * @param integer $id Id.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function showUserPaymentAction($id)
    {
        $user = $this->getRepository('FaUserBundle:User')->find($id);
        try {
            if (!$user) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaPaymentBundle:Payment'), $this->getRepositoryTable('FaPaymentBundle:Payment'), 'fa_user_user_payment_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['query_joins']   = array('payment' => array('user' => array('type' => 'inner')));
        $data['select_fields'] = array('payment' => array('id', 'payment_method', 'is_action_by_admin', 'cart_code', 'created_at', 'amount'));
        $data['static_filters'] = PaymentRepository::ALIAS.'.user = '.$id;

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaPaymentBundle:Payment'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $paypalPaymentIds      = array();
        $cyberSourcePaymentIds = array();

        if ($pagination->getNbResults()) {
            foreach ($pagination->getCurrentPageResults() as $arrayPayment) {
                if ($arrayPayment['payment_method'] == 'paypal') {
                    $paypalPaymentIds[$arrayPayment['id']] = $arrayPayment['id'];
                } elseif ($arrayPayment['payment_method'] == 'cybersource') {
                    $cyberSourcePaymentIds[$arrayPayment['id']] = $arrayPayment['id'];
                }
            }

            if (count($paypalPaymentIds) > 0) {
                $objPaypalPayments = $this->getRepository('FaPaymentBundle:PaymentPaypal')->findByPayment($paypalPaymentIds);
                if ($objPaypalPayments) {
                    foreach ($objPaypalPayments as $objPaypalPayment) {
                        $paypalPaymentIds[$objPaypalPayment->getPayment()->getId()] = $objPaypalPayment->getIp();
                    }
                }
            }

            if (count($cyberSourcePaymentIds) > 0) {
                $objCyberSourcePayments = $this->getRepository('FaPaymentBundle:PaymentCyberSource')->findByPayment($cyberSourcePaymentIds);
                if ($objCyberSourcePayments) {
                    foreach ($objCyberSourcePayments as $objCyberSourcePayment) {
                        $cyberSourcePaymentIds[$objCyberSourcePayment->getPayment()->getId()] = $objCyberSourcePayment->getIp();
                    }
                }
            }
        }

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserPaymentSearchType::class, null, array('action' => $this->generateUrl('user_payment_list_admin', array('id' => $id)), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'user'                  => $user,
            'heading'               => $this->get('translator')->trans('User detail'),
            'pagination'            => $pagination,
            'sorter'                => $data['sorter'],
            'form'                  => $form->createView(),
            'paypalPaymentIds'      => $paypalPaymentIds,
            'cyberSourcePaymentIds' => $cyberSourcePaymentIds,
        );

        return $this->render('FaUserBundle:UserAdmin:show.html.twig', $parameters);
    }

    /**
     * Boost Overide of User entity.
     *
     * @param integer $id      Id.
     * @param Request $request A Request object.
     *
     * @throws NotFoundHttpException
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function boostOverideAction($id, Request $request)
    {
        if (!empty($id) && strpos($id, ',') == true) {
            $userIds = explode(',', $id);
        } elseif (!is_array($id)) {
            $userIds = array($id);
        }

        $goBackUrl = null;
        $session = $this->container->get('session');
        if ($session->has('go_back_url')) {
            $goBackUrl = $session->get('go_back_url');
        } else {
            $goBackUrl = CommonManager::getAdminBackUrl($this->container);
            $session->set('go_back_url', $goBackUrl);
        }

        if ($request->getMethod() != 'PUT') {
            CommonManager::setAdminBackUrl($request, $this->container);
        }

        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entities = $this->getRepository('FaUserBundle:User')->findBy(array('id' => $userIds));

        try {
            if (!$entities) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find User entity.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', 'user_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('user_boost_overide', array('id' => $id)),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(BoostOverideType::class, $entities[0], $options);


        if ($formManager->isValid($form)) {
            try {
                foreach ($entities as $entity) {
                    $this->getEntityManager()->beginTransaction();
                    $userPackageId = $form->get('user_package_id')->getData();
                    $userBoostOveride = $form->get('boost_overide')->getData();

                    $userPackage = $this->getRepository('FaUserBundle:UserPackage')->find($userPackageId);
                    if ($userPackage) {
                        $userPackage->setBoostOveride($userBoostOveride);
                        $this->getEntityManager()->persist($userPackage);
                    }
                    $this->getEntityManager()->getConnection()->commit();
                }

                $this->getEntityManager()->flush();
                return $this->handleMessage($this->get('translator')->trans('Boost count has been overiden successfully.'), ($goBackUrl ? $goBackUrl : 'user_admin'));
            } catch (\Exception $e) {
                $this->getEntityManager()->getConnection()->rollback();
                CommonManager::sendErrorMail($this->container, 'Error: Problem in Changing user boost advert maximum number', $e->getMessage(), $e->getTraceAsString());
                return $this->handleMessage($this->get('translator')->trans('Sorry there is an issue in updating user boost count.'), ($backUrl ? $backUrl : 'user_admin'));
            }
        }

        $parameters = array(
            'entities' => $entities,
            'userIds'  => $id,
            'form'     => $form->createView(),
            'heading'  => 'Boost Overide',
            'goBackUrl' => $goBackUrl
        );

        return $this->render('FaUserBundle:UserAdmin:boostOveride.html.twig', $parameters);
    }
}
