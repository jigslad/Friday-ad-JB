<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EmailBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\EmailBundle\Entity\EmailTemplate;
use Fa\Bundle\EmailBundle\Form\EmailTemplateAdminType;
use Fa\Bundle\EmailBundle\Form\EmailTemplateSearchAdminType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EmailBundle\Entity\EmailTemplateSchedule;
// use Fa\Bundle\EmailBundle\Entity\EmailTemplateParams;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EmailBundle\Form\EmailTemplateScheduleAdminType;
use Fa\Bundle\EmailBundle\Form\EmailTemplateParamsAdminType;

/**
 * This controller is used for email template management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class EmailTemplateAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Lists all email template.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaEmailBundle:EmailTemplate'), $this->getRepositoryTable('FaEmailBundle:EmailTemplate'), 'fa_email_template_email_template_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields']  = array('email_template' => array('id', 'subject', 'name', 'status', 'identifier', 'schedual'));

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaEmailBundle:EmailTemplate'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(EmailTemplateSearchAdminType::class, null, array('action' => $this->generateUrl('email_template_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray' => EntityRepository::getStatusArray($this->container),
            'heading'     => $this->get('translator')->trans('Email Templates'),
            'form'        => $form->createView(),
            'pagination'  => $pagination,
            'sorter'      => $data['sorter'],
        );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:index.html.twig', $parameters);
    }

    /**
     * Creates a new email template.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = new EmailTemplate();

        $options =  array(
                      'action' => $this->generateUrl('email_template_create_admin'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm(EmailTemplateAdminType::class, $entity, $options);

        if ($formManager->isValid($form)) {
            $formManager->save($entity);

            $redirectRouteParams = array();
            $redirectRoute       = 'email_template_admin';

            if ($form->get('saveAndNew')->isClicked()) {
                $redirectRoute = 'email_template_new_admin';
            } elseif ($form->get('saveAndPreview')->isClicked()) {
                $redirectRoute             = 'email_template_preview_admin';
                $redirectRouteParams['id'] = $entity->getId();
                $backUrl                   = null;
            }

            return parent::handleMessage($this->get('translator')->trans('Email template was successfully added.', array(), 'success'), ($backUrl ? $backUrl : $redirectRoute), $redirectRouteParams);

        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New Email Template'),
                      );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to create a new email template.
     *
     * @param Request $request A Request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager = $this->get('fa.formmanager');

        $entity = new EmailTemplate();

        $form = $formManager->createForm(EmailTemplateAdminType::class, $entity, array('action' => $this->generateUrl('email_template_create_admin')));

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New Email Template'),
                      );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:new.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing email template.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaEmailBundle:EmailTemplate')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find email template.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'email_template_admin');
        }

        $options =  array(
                      'action' => $this->generateUrl('email_template_update_admin', array('id' => $entity->getId())),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm(EmailTemplateAdminType::class, $entity, $options);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit Email Template'),
                      );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:new.html.twig', $parameters);
    }

    /**
     * Edits an existing email template.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository('FaEmailBundle:EmailTemplate')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find email template.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'email_template_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('email_template_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm(EmailTemplateAdminType::class, $entity, $options);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();

            $redirectRouteParams = array();
            $redirectRoute       = 'email_template_admin';

            if ($form->get('saveAndNew')->isClicked()) {
                $redirectRoute = 'email_template_new_admin';
            } elseif ($form->get('saveAndPreview')->isClicked()) {
                $redirectRoute             = 'email_template_preview_admin';
                $redirectRouteParams['id'] = $entity->getId();
                $backUrl                   = null;
            }

            return parent::handleMessage($this->get('translator')->trans('Email template was successfully updated.', array(), 'success'), ($backUrl ? $backUrl : $redirectRoute), $redirectRouteParams);
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit Email Template'),
                       );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:new.html.twig', $parameters);
    }

    /**
     * Deletes a email template.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl = CommonManager::getAdminBackUrl($this->container);
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $entity = $this->getRepository('FaEmailBundle:EmailTemplate')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find email template.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'email_template_admin');
        }

        $deleteManager->delete($entity);

        return parent::handleMessage($this->get('translator')->trans('Email template was successfully deleted.'), ($backUrl ? $backUrl : 'email_template_admin'));
    }

    /**
     * Displays a email template preview.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function previewAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $emailTemplate       = $this->getRepository('FaEmailBundle:EmailTemplate')->find($id);
        $emailTemplateLayout = $this->getRepository('FaEmailBundle:EmailTemplate')->findOneBy(array('identifier' => 'email_template_layout'));

        try {
            if (!$emailTemplate) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find email template.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'email_template_admin');
        }

        $parameters = array(
            'emailTemplate'       => $emailTemplate,
            'emailTemplateLayout' => $emailTemplateLayout,
            'heading'             => $this->get('translator')->trans('Email Template Preview'),
        );

        if ($request->get('onlyPreview')) {
            return $this->render('FaEmailBundle:EmailTemplateAdmin:iframePreview.html.twig', $parameters);
        } else {
            return $this->render('FaEmailBundle:EmailTemplateAdmin:preview.html.twig', $parameters);
        }
    }

    /**
     * Schedule a email template.
     *
     * @param integer $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function scheduleAction($id, Request $request)
    {
        $emailTemplate = $this->getRepository('FaEmailBundle:EmailTemplate')->find($id);
        try {
            if (!$emailTemplate) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find email template.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'email_template_admin');
        }

        $emailTemplateSchedule = $this->getRepository('FaEmailBundle:EmailTemplateSchedule')->findOneBy(array('email_template' => $id));
        $formManager = $this->get('fa.formmanager');
        if (!$emailTemplateSchedule) {
            $emailTemplateSchedule = new EmailTemplateSchedule();
        }

        $form       = $formManager->createForm(EmailTemplateScheduleAdminType::class, $emailTemplateSchedule);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $formManager->save($emailTemplateSchedule);

                $response = $this->handleMessage($this->get('translator')->trans('Email template schedule updated successfully.'), 'email_template_admin');
                return $response;
            }
        }

        $parameters = array(
            'emailTemplate' => $emailTemplate,
            'form'          => $form->createView(),
            'heading'       => $this->get('translator')->trans('Email Template Schedule'),
        );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:schedule.html.twig', $parameters);
    }

    /**
     * set parameters for email template.
     *
     * @param integer $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function paramsAction($id, Request $request)
    {
        $emailTemplate = $this->getRepository('FaEmailBundle:EmailTemplate')->find($id);

        try {
            if (!$emailTemplate) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find schedual.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'email_template_admin');
        }

        if ($emailTemplate->getSchedual() == 0) {
            $parameters = array(
                'emailTemplate' => $emailTemplate,
                'heading'       => $this->get('translator')->trans('Email Template Parameters'),
            );
            return $this->render('FaEmailBundle:EmailTemplateAdmin:params.html.twig', $parameters);
        }

        $formManager = $this->get('fa.formmanager');

        $form = $formManager->createForm(EmailTemplateParamsAdminType::class, null, array('attr' => array('template_id' => $emailTemplate->getId())));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $response = $this->handleMessage($this->get('translator')->trans('Email template parameters updated successfully.'), 'email_template_admin');
                return $response;
            }
        }

        $parameters = array(
            'emailTemplate' => $emailTemplate,
            'form'          => $form->createView(),
            'heading'       => $this->get('translator')->trans('Email Template Parameters'),
        );

        return $this->render('FaEmailBundle:EmailTemplateAdmin:params.html.twig', $parameters);
    }
}
