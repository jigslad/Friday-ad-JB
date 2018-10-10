<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\ContentBundle\Entity\LandingPage;
use Fa\Bundle\ContentBundle\Form\LandingPageSearchAdminType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\ContentBundle\Form\LandingPageAdminType;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for Landing page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LandingPageAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'landing_page';
    }

    /**
     * Lists all Entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:LandingPage'), $this->getRepositoryTable('FaContentBundle:LandingPage'), 'fa_content_landing_page_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('landing_page' => array('id', 'h1_tag', 'type', 'status'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:LandingPage'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(LandingPageSearchAdminType::class, null, array('action' => $this->generateUrl('landing_page_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Landing Pages'),
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaContentBundle:LandingPageAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new Location entity.
     *
     * @param Request $request Request instance
     *
     * @return JsonResponse
     */
    public function ajaxGetDimensionAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            // initialize form manager service
            $formManager = $this->get('fa.formmanager');

            $entity = new LandingPage();

            $options =  array();

            $form = $formManager->createForm(LandingPageAdminType::class, $entity, $options);

            //unset unwanted fields
            $unsetFields = array(
                'description',
                'file',
                'type',
                'h1_tag',
                'meta_description',
                'meta_keywords',
                'url_key',
                'page_title',
                'status',
                'colour_id',
                'save',
                'saveAndNew',
            );

            foreach ($unsetFields as $unsetField) {
                $form->remove($unsetField);
            }

            $parameters = array(
                'entity'  => $entity,
                'form'    => $form->createView(),
            );

            $response         = array();
            $response['html'] = $this->renderView('FaContentBundle:LandingPageAdmin:ajaxGetDimension.html.twig', $parameters);

            return new JsonResponse($response);
        } else {
            return new Response();
        }
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
        $imagesArray = array();
        $categoryId  = null;
        $params = $request->get('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        if (isset($params['category'])) {
            $categoryId = $params['category'];
        }
        $landingPageImageArray = $this->getEntityManager()->getRepository('FaContentBundle:LandingPageInfo')->getLandingPageImageArray($this->container);
        if (isset($landingPageImageArray[$categoryId]) && count($landingPageImageArray[$categoryId])) {
            $imagesArray = $landingPageImageArray[$categoryId];
        }

        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getEntity();
        if ($categoryId) {
            $entity->setCategory($this->getEntityManager()->getReference('FaEntityBundle:Category', $categoryId));
        }
        if (isset($params['type'])) {
            $entity->setType($params['type']);
        }
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form        = $formManager->createForm($fqn, $entity, array('action' => $this->generateUrl($this->getRouteName('create'))));
        $parameters  = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
            'imagesArray' => $imagesArray,
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
        $imagesArray = array();
        $categoryId  = null;
        $params = $request->get('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        if (isset($params['category'])) {
            $categoryId = $params['category'];
        }
        $landingPageImageArray = $this->getEntityManager()->getRepository('FaContentBundle:LandingPageInfo')->getLandingPageImageArray($this->container);
        if (isset($landingPageImageArray[$categoryId]) && count($landingPageImageArray[$categoryId])) {
            $imagesArray = $landingPageImageArray[$categoryId];
        }

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
            if($form->has('saveAndNew') &&  $form->get('saveAndNew')->isClicked()) {
                $redirectPath = $this->getRouteName('create');
            }

            return $this->handleMessage($message, $redirectPath);
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
            'imagesArray' => $imagesArray,
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

        $imagesArray = array();
        $categoryId  = $entity->getCategory()->getId();
        $landingPageImageArray = $this->getEntityManager()->getRepository('FaContentBundle:LandingPageInfo')->getLandingPageImageArray($this->container);

        if (isset($landingPageImageArray[$categoryId]) && count($landingPageImageArray[$categoryId])) {
            $imagesArray = $landingPageImageArray[$categoryId];
        }

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
            'imagesArray' => $imagesArray,
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

        $imagesArray = array();
        $categoryId  = $entity->getCategory()->getId();
        $landingPageImageArray = $this->getEntityManager()->getRepository('FaContentBundle:LandingPageInfo')->getLandingPageImageArray($this->container);
        if (isset($landingPageImageArray[$categoryId]) && count($landingPageImageArray[$categoryId])) {
            $imagesArray = $landingPageImageArray[$categoryId];
        }

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
            if($form->has('saveAndNew') &&  $form->get('saveAndNew')->isClicked()) {
                $redirectPath = $this->getRouteName('create');
            }

            return $this->handleMessage($message, $redirectPath);
        }

        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
            'imagesArray' => $imagesArray,
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
        $backUrl = CommonManager::getAdminBackUrl($this->container);

        // create array of landing page info images.
        $imagesArray       = array();
        $landingPageImages = $this->getRepository('FaContentBundle:LandingPageInfo')->findBy(array('landing_page' => $id));
        $imagePath         = $this->container->get('kernel')->getRootDir().'/../web/';

        foreach ($landingPageImages as $landingPageImage) {
            $file = $imagePath.$landingPageImage->getPath().'/'.$landingPageImage->getFileName();
            if ($landingPageImage->getFileName() && file_exists($file)) {
                $imagesArray[] = $file;
            }

            $overlayFile = $imagePath.$landingPageImage->getPath().'/'.$landingPageImage->getOverlayFileName();
            if ($landingPageImage->getOverlayFileName() && file_exists($overlayFile)) {
                $imagesArray[] = $overlayFile;
            }
        }

        CommonManager::setAdminBackUrl($request, $this->container);
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
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), $this->getRouteName(''), array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', $this->getRouteName(''));
        }

        // remove images of landing page info.
        $landingPageImages = $this->getRepository('FaContentBundle:LandingPageInfo')->findBy(array('landing_page' => $id));
        $imagePath = $this->container->get('kernel')->getRootDir().'/../web/';

        // remove images of landing page info.
        foreach ($imagesArray as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
    
    public function getFQNForForms($formName)
    {
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_content_landing_page_admin' => LandingPageAdminType::class
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
    }
}
