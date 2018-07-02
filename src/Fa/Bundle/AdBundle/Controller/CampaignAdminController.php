<?php
/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Form\CampaignsAdminType;
use Fa\Bundle\AdBundle\Form\CampaignsAdminSearchType;
use Fa\Bundle\AdBundle\Entity\Campaigns;
use Fa\Bundle\AdBundle\Repository\CampaignsRepository;
use \Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This controller is used for paa field rule crud management.
 *
 * @author Rohini <rohini.subburam@fridaymediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class CampaignAdminController extends CrudController implements ResourceAuthorizationController
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
	public function getTableName()
	{
		return 'campaigns';
	}

    /**
     * Lists all Campaign entities.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Campaigns'), $this->getRepositoryTable('FaAdBundle:Campaigns'), 'fa_ad_campaigns_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
            'campaigns' => array(
                'id',
                'campaignName',
                'pageTitle',
                'campaignStatus',
                'slug',
                'is_not_deletable',
            ),
            'category'       => array('id as category_id','lvl as category_lvl'),
        );
        $data['query_joins'] = array(
            'campaigns' => array(
                'category' => array('type' => 'left'),
            )
        );
        $data['sort_field'] = array(
            'campaigns' => array(
                'campaignName',
                'pageTitle',
                'campaignStatus',
                'category',
            )
        );
        
        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:Campaigns'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm('fa_ad_campaigns_search_admin', null, array(
            'action' => $this->generateUrl('campaigns_admin'),
            'method' => 'GET'
        ));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading' => 'Paa Lite',
            'form' => $form->createView(),
        );

        $parameters['pagination'] = $pagination;
        $parameters['sorter'] = $data['sorter'];

        return $this->render('FaAdBundle:CampaignAdmin:index.html.twig', $parameters);

    }

    public function newAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $retId = 0;
        
        $entity = new Campaigns();

        $form = $formManager->createForm('fa_ad_campaigns_admin', $entity, array('action' => $this->generateUrl('campaigns_create_admin')));

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New Paa Lite'),
                      );

        return $this->render('FaAdBundle:CampaignAdmin:new.html.twig', $parameters);
    }
    
    /**
     * Displays a form to create a new record.
     *
     * @param integer $category_id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newFromCategoryAction($category_id, Request $request)
    {
        $retId = 0;
       
        $entity      = $this->getEntity();
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin', $entity, array('action' => $this->generateUrl($this->getRouteName('create'))));

        $parameters = array(
                           'entity'  => $entity,
                           'form'    => $form->createView(),
                           'heading' => $this->get('translator')->trans('New Paa Lite'),
                      );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
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

        $entity = new Campaigns();

        $options =  array(
                      'action' => $this->generateUrl('campaigns_create_admin'),
                      'method' => 'POST'
                    );

        $form = $formManager->createForm('fa_ad_campaigns_admin', $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
          return parent::handleMessage($this->get('translator')->trans('Campaign was successfully added.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'campaigns_new_admin' : 'campaigns_admin'));
        }

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('New Paa Lite'),
                      );

        return $this->render('FaAdBundle:CampaignAdmin:new.html.twig', $parameters);
    }

    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');

        $entity = $this->getRepository('FaAdBundle:Campaigns')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find campaign.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'campaigns');
        }

        $options =  array(
                      'action' => $this->generateUrl('campaigns_update_admin', array('id' => $entity->getId())),
                      'method' => 'PUT'
                    );

        $form = $formManager->createForm('fa_ad_campaigns_admin', $entity, $options);

        $this->unsetFormFields($form);

        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit Paa Lite'),
                      );

        return $this->render('FaAdBundle:CampaignAdmin:new.html.twig', $parameters);
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
        $entity      = $this->getRepository('FaAdBundle:Campaigns')->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find campaign.'));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'campaigns_admin');
        }

        $options =  array(
            'action' => $this->generateUrl('campaigns_update_admin', array('id' => $entity->getId())),
            'method' => 'PUT'
        );

        $form = $formManager->createForm('fa_ad_campaigns_admin', $entity, $options);

        $this->unsetFormFields($form);

        if ($formManager->isValid($form)) {
            $this->getEntityManager()->flush();
            //$this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Ad status has been changed successfully.'), 'success');
            //return $this->redirect($formData['return_url']);
            return parent::handleMessage($this->get('translator')->trans('Campaign was successfully updated.', array(), 'success'), ($form->get('saveAndNew')->isClicked() ? 'campaigns_new_admin' : $backUrl));
        }
        
        $parameters = array(
                        'entity'  => $entity,
                        'form'    => $form->createView(),
                        'heading' => $this->get('translator')->trans('Edit Paa Lite'),
                       );

        return $this->render('FaAdBundle:CampaignAdmin:new.html.twig', $parameters);
    }

    public function ajaxCheckCampaignSlugExistAction(Request $request) {  
        $slug = $request->request->get('slug');
        $countCampaign = 0;
        //$slug = 'motor_boats';
        $campaign = $this->getRepository('FaAdBundle:Campaigns')->findOneBy(array('slug' => $slug));
        $countCampaign = count($campaign);
        return new JsonResponse(array('campaigncount' => $countCampaign));
    }

    /**
     * Deletes a Campaign.
     *
     * @param Request $request A Request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function deleteAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize form manager service
        $deleteManager = $this->get('fa.deletemanager');

        $campaign = null;
        if ($id) {
          $campaign = $this->getRepository('FaAdBundle:Campaigns')->find($id);
        }
        
        try {
          if (!$campaign) {
           throw $this->createNotFoundException($this->get('translator')->trans('Unable to find campaign.'));
          }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return parent::handleException($e, 'error', 'campaigns_admin');
        }

        try {
            $deleteManager->delete($campaign);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This campaign can not be removed from database because it's reference exists in database.", array(), 'error'), 'campaigns_admin', array(), 'error');
        } catch (\Exception $e) {
              return parent::handleException($e, 'error', 'campaigns_admin');
        }
        

        return parent::handleMessage($this->get('translator')->trans('Campaign was successfully deleted.', array(), 'success'),  'campaigns_admin');
    }
}
