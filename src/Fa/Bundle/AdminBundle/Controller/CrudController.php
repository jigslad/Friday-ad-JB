<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdminBundle\Controller;

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Entity\Resource;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Form\TestimonialsAdminType;
use Fa\Bundle\AdBundle\Form\SearchKeywordAdminType;
use Fa\Bundle\PromotionBundle\Form\UpsellAdminType;
use Fa\Bundle\CoreBundle\Form\ConfigRuleAdminType;
use Fa\Bundle\EntityBundle\Form\LocationGroupAdminType;
use Fa\Bundle\EntityBundle\Form\DimensionAdminType;
use Fa\Bundle\PaymentBundle\Form\DeliveryMethodOptionAdminType;
use Fa\Bundle\AdBundle\Form\PaaFieldRuleAdminType;
use Fa\Bundle\AdBundle\Form\PrintEditionAdminType;
use Fa\Bundle\ContentBundle\Form\SeoToolAdminType;
use Fa\Bundle\ContentBundle\Form\HeaderImageAdminType;
use Fa\Bundle\ContentBundle\Form\StaticBlockAdminType;
use Fa\Bundle\ContentBundle\Form\StaticPageAdminType;
use Fa\Bundle\ContentBundle\Form\HomePopularImageAdminType;
use Fa\Bundle\ContentBundle\Form\SeoToolOverrideAdminType;
use Fa\Bundle\UserBundle\Form\UserConfigRuleAdminType;
use Fa\Bundle\EntityBundle\Form\CategoryAdminType;
use Fa\Bundle\AdBundle\Form\PrintDeadlineAdminType;
use Fa\Bundle\AdBundle\Form\LocationRadiusAdminType;
use Fa\Bundle\AdFeedBundle\Form\AdFeedMappingAdminType;
use Fa\Bundle\PromotionBundle\Form\CategoryUpsellAdminType;
use Fa\Bundle\UserBundle\Form\BoostOverrideAdminType;

/**
 * This controller is used for basic crud management.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
abstract class CrudController extends CoreController
{
    /**
     * Save new using form.
     *
     * @var boolean
     */
    protected $saveNewUsingForm  = false;

    /**
     * Save edit using form.
     *
     * @var boolean
     */
    protected $saveEditUsingForm = false;

    /**
     *  Get name of table.
     */
    abstract protected function getTableName();

    /**
     * Get doctrine entity with namespace.
     *
     * @return string
     */
    protected function getEntityWithNamespace()
    {
        return '\\Fa\\Bundle\\'.ucwords($this->getBundleAlias()).'Bundle\\Entity\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $this->getTableName())));
    }

    /**
     * Get name of entity.
     *
     * @return string
     */
    protected function getEntityName()
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $this->getTableName())));
    }

    /**
     * Get entity.
     *
     * @return object
     */
    protected function getEntity()
    {
        $entityName = $this->getEntityWithNamespace();
        return new $entityName();
    }

    /**
     * Get route name.
     *
     * @param string $name Name of route.
     *
     * @return string
     */
    protected function getRouteName($name = 'create')
    {
        if ($name != '') {
            return $this->getTableName().'_'.$name.'_admin';
        } else {
            return $this->getTableName().'_admin';
        }
    }

    /**
     * Get name of bundle.
     *
     * @return string
     */
    protected function getBundleName()
    {
        $matches    = array();
        $controller = $this->container->get('request_stack')->getCurrentRequest()->attributes->get('_controller');
        preg_match('/(.*)\\\Bundle\\\(.*)\\\Controller\\\(.*)Controller::(.*)Action/', $controller, $matches);
        return 'Fa'.$matches[2];
    }

    /**
     * Get alias of bundle.
     *
     * @return string
     */
    protected function getBundleAlias()
    {
        return strtolower(str_replace('Bundle', '', str_replace('Fa', '', $this->getBundleName())));
    }

    /**
     * Get name of controller.
     *
     * @return string
     */
    protected function getControllerName()
    {
        $matches    = array();
        $controller = $this->container->get('request_stack')->getCurrentRequest()->attributes->get('_controller');
        preg_match('/(.*)\\\Bundle\\\(.*)\\\Controller\\\(.*)Controller::(.*)Action/', $controller, $matches);
        return $matches[3];
    }

    /**
     * Get word to be displayed on template.
     *
     * @return string
     */
    protected function getDisplayWord()
    {
        return ucwords(str_replace('_', ' ', $this->getTableName()));
    }

    /**
     * Displays a form to create a new record.
     *
     * @param Request $request A request object.
     *
     * @return Response A response object.
     */
    public function newAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getEntity();
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form        = $formManager->createForm($fqn, $entity, array('action' => $this->generateUrl($this->getRouteName('create'))));
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
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getEntity();
        $options     = array(
                           'action' => $this->generateUrl($this->getRouteName('create')),
                           'method' => 'POST'
                       );

        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);
        if ($formManager->isValid($form)) {
            if (!$this->saveNewUsingForm) {
                $formManager->save($entity);
            }
            $message = $this->get('translator')->trans('Record has been added successfully.', array(), 'success');

            $redirectPath = $this->getRouteName('');
            // Check if user clicked on save and new then open new form
            if ($form->has('saveAndNew') &&  $form->get('saveAndNew')->isClicked()) {
                $redirectPath = $this->getRouteName('create');
            }

            return $this->handleMessage($message, $redirectPath);
        }

        $parameters = array(
                          'entity'  => $entity,
                          'form'    => $form->createView(),
                          'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
                      );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
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
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        $options =  array(
                        'action' => $this->generateUrl($this->getRouteName('update'), array('id' => $entity->getId())),
                        'method' => 'PUT'
                    );

        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
                          'entity'  => $entity,
                          'form'    => $form->createView(),
                          'heading' => $this->get('translator')->trans('Edit %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
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
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        $options = array(
                       'action' => $this->generateUrl($this->getRouteName('update'), array('id' => $entity->getId())),
                       'method' => 'PUT'
                   );
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);

        if ($formManager->isValid($form)) {
            if (!$this->saveEditUsingForm) {
                $this->getEntityManager()->flush();
            }

            $message = $this->get('translator')->trans('Record has been updated successfully.', array(), 'success');

            $redirectPath = $this->getRouteName('');
            // Check if user clicked on save and new then open new form
            if ($form->has('saveAndNew') &&  $form->get('saveAndNew')->isClicked()) {
                $redirectPath = $this->getRouteName('create');
            }
            return $this->handleMessage($message, $redirectPath);
        }

        $parameters = array(
                          'entity'  => $entity,
                          'form'    => $form->createView(),
                          'heading' => $this->get('translator')->trans('Edit %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
                      );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }

    /**
     * Deletes a record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     */
    public function deleteAction(Request $request, $id)
    {
        //CommonManager::setAdminBackUrl($request, $this->container);
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
            $deleteManager->delete($entity);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), ($backUrl ? $backUrl : $this->getRouteName('')), array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', ($backUrl ? $backUrl : $this->getRouteName('')));
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
    
    public function getFQNForForms($formName)
    {
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_user_testimonials_admin' => TestimonialsAdminType::class,
            'fa_ad_search_keyword_admin' => SearchKeywordAdminType::class,
            'fa_promotion_upsell_admin' => UpsellAdminType::class,
            'fa_core_config_rule_admin' => ConfigRuleAdminType::class,
            'fa_entity_location_group_admin' => LocationGroupAdminType::class,
            'fa_entity_dimension_admin' => DimensionAdminType::class,
            'fa_payment_delivery_method_option_admin' => DeliveryMethodOptionAdminType::class,
            'fa_ad_paa_field_rule_admin' => PaaFieldRuleAdminType::class,
            'fa_ad_print_edition_admin' => PrintEditionAdminType::class,
            'fa_content_seo_tool_admin' => SeoToolAdminType::class,
            'fa_content_header_image_admin' => HeaderImageAdminType::class,
            'fa_content_static_block_admin' => StaticBlockAdminType::class,
            'fa_content_static_page_admin' => StaticPageAdminType::class,
            'fa_content_home_popular_image_admin' => HomePopularImageAdminType::class,
            'fa_content_seo_tool_override_admin' => SeoToolOverrideAdminType::class,
            'fa_user_user_config_rule_admin' => UserConfigRuleAdminType::class,
            'fa_entity_category_admin' => CategoryAdminType::class,
            'fa_ad_print_deadline_admin' => PrintDeadlineAdminType::class,
            'fa_ad_location_radius_admin' => LocationRadiusAdminType::class,
            'fa_adfeed_ad_feed_mapping_admin' => AdFeedMappingAdminType::class,
            'fa_promotion_category_upsell_admin' => CategoryUpsellAdminType::class,
            'fa_user_boost_override_admin' => BoostOverrideAdminType::class,
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
    }
}
