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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\AdNimber;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\PaymentBundle\Repository\DeliveryMethodOptionRepository;
use Fa\Bundle\AdBundle\Form\NimberPostcodeType;
use Fa\Bundle\AdBundle\Form\NimberCreateTaskType;

/**
 * This controller is used for nimber delivery option.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class NimberController extends CoreController
{
    /**
     * Show nimber price suggestion action.
     *
     * @param integer $adId    Ad id.
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function showNimberPricesuggestionAction($adId, Request $request)
    {
        $htmlContent   = '';
        $error   = '';
        if ($request->get('clear') == 1) {
            $response = new Response();
            $response->headers->clearCookie('nimber_price_suggestion_'.$adId);
            $response->sendHeaders();

            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(NimberPostcodeType::class);
            $htmlContent = $this->renderView('FaAdBundle:Nimber:showNimberLocation.html.twig', array('form' => $form->createView(), 'adId' => $adId, 'js' => true));
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        }
        if ($request->cookies->has('nimber_price_suggestion_'.$adId) && $request->cookies->get('nimber_price_suggestion_'.$adId) != CommonManager::COOKIE_DELETED) {
            $priceSuggestion = unserialize($request->cookies->get('nimber_price_suggestion_'.$adId));
            return $this->render('FaAdBundle:Nimber:showNimberPriceSuggestion.html.twig', array('adId' => $adId, 'priceSuggestion' => $priceSuggestion));
        } else {
            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(NimberPostcodeType::class);

            if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod()) {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $formParams = $request->get('fa_nimber_postcode');
                    $adObj = $this->getEntityManager()->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId, 'status' => EntityRepository::AD_STATUS_LIVE_ID));
                    if ($adObj && $adObj->getCategory()) {
                        $locationObj = $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->findOneBy(array('ad' => $adId));
                        if ($locationObj) {
                            $to = $formParams['zip'];
                            $from = ($locationObj->getPostcode() ? $locationObj->getPostcode() : ($locationObj->getLocationTown() ? $locationObj->getLocationTown()->getName() : null));
                            $nimberOptions = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getNimberDetailForCategoryId($adObj->getCategory()->getId(), $this->container);
                            $size = $nimberOptions['nimber_size'];
                            if ($from && $to) {
                                $priceSuggestion = $this->container->get('fa.nimber.manager')->getPriceSuggestion($from, $to, $size);
                                if (isset($priceSuggestion['error']) && $priceSuggestion['error']) {
                                    $error = $priceSuggestion['error'];
                                } else {
                                    //set new cookies for nimber location.
                                    $priceSuggestion['to'] = $to;
                                    $priceSuggestion['from'] = $from;
                                    $response = new Response();
                                    $response->headers->setCookie(new Cookie('nimber_price_suggestion_'.$adId, serialize($priceSuggestion), time() + 15 * 60));
                                    $response->sendHeaders();

                                    $htmlContent = $this->renderView('FaAdBundle:Nimber:showNimberPriceSuggestion.html.twig', array('adId' => $adId, 'priceSuggestion' => $priceSuggestion));
                                }
                            } else {
                                $error = $this->get('translator')->trans('Unable to calculate cost.', array(), 'frontend-nimber');
                            }
                        } else {
                            $error = $this->get('translator')->trans('Unable to find ad location.', array(), 'frontend-nimber');
                        }
                    } else {
                        $error = $this->get('translator')->trans('Unable to find ad or ad category.', array(), 'frontend-nimber');
                    }
                } else {
                    $htmlContent = $this->renderView('FaAdBundle:Nimber:showNimberLocation.html.twig', array('form' => $form->createView(), 'adId' => $adId, 'js' => true));
                }
                return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
            }

            if ($request->isXmlHttpRequest()) {
                $htmlContent = $this->renderView('FaAdBundle:Nimber:showNimberLocation.html.twig', array('form' => $form->createView(), 'adId' => $adId, 'js' => true));
                return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
            } else {
                return $this->render('FaAdBundle:Nimber:showNimberLocation.html.twig', array('form' => $form->createView(), 'adId' => $adId, 'js' => false));
            }
        }
    }

    /**
     * Nimber create task.
     *
     * @param integer $adId    Ad id.
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxNimberCreateTaskAction($adId, Request $request)
    {
        $error         = '';
        $nimberError   = '';
        $nimberTaskId  = null;
        $nimberTaskUrl = null;
        $htmlContent   = '';
        $priceSuggestion = array();

        if ($request->isXmlHttpRequest()) {
            if ($request->cookies->has('nimber_price_suggestion_'.$adId) && $request->cookies->get('nimber_price_suggestion_'.$adId) != CommonManager::COOKIE_DELETED) {
                $priceSuggestion = unserialize($request->cookies->get('nimber_price_suggestion_'.$adId));
            }
            $adObj = $this->getEntityManager()->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId, 'status' => EntityRepository::AD_STATUS_LIVE_ID));
            $nimberOptions = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getNimberDetailForCategoryId($adObj->getCategory()->getId(), $this->container);
            if (isset($nimberOptions['is_nimber_enabled']) && !$nimberOptions['is_nimber_enabled']) {
                $error = $this->get('translator')->trans('You do not have permission to access this resource.', array(), 'frontend-nimber');
            }
            if (!$error && $adObj && $adObj->getCategory()) {
                if ($adObj->getCategory() && $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getRootCategoryId($adObj->getCategory()->getId(), $this->container) != CategoryRepository::FOR_SALE_ID) {
                    $error = $this->get('translator')->trans('Unable to create nimber task for this category other than For Sale.', array(), 'frontend-nimber');
                } elseif (!$adObj->getDeliveryMethodOption() ||  (!in_array($adObj->getDeliveryMethodOption()->getId(), array(DeliveryMethodOptionRepository::COLLECTION_ONLY_ID, DeliveryMethodOptionRepository::POSTED_OR_COLLECT_ID)))) {
                    $error = $this->get('translator')->trans('Unable to create nimber task due to invalid delivery option.', array(), 'frontend-nimber');
                } else {
                    $locationObj = $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->findOneBy(array('ad' => $adId));
                    if ($locationObj) {
                        $to = (isset($priceSuggestion['to']) ? $priceSuggestion['to'] : null);
                        $from = (isset($priceSuggestion['from']) ? $priceSuggestion['from'] : null);
                        $size = $nimberOptions['nimber_size'];
                        if ($from && $to) {
                            $priceSuggestion = $this->container->get('fa.nimber.manager')->getPriceSuggestion($from, $to, $size);
                            if (isset($priceSuggestion['error']) && $priceSuggestion['error']) {
                                $error = $priceSuggestion['error'];
                            }
                        } else {
                            $error = 'no_cookie';
                        }
                    } else {
                        $error = $this->get('translator')->trans('Unable to find ad location.', array(), 'frontend-nimber');
                    }
                }
            } else {
                $error = $this->get('translator')->trans('Unable to find live ad or ad category.', array(), 'frontend-nimber');
            }

            if (!$error) {
                $formManager = $this->get('fa.formmanager');
                $adNimber = new AdNimber();
                $form = $formManager->createForm(NimberCreateTaskType::class, $adNimber, array('adId' => $adId));

                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $formParams = $request->get('fa_nimber_create_task');
                        $nimberPostParams = array();
                        $nimberPostParams['title'] = $adObj->getTitle();
                        $nimberPostParams['price'] = $priceSuggestion['price_suggestion'];
                        $nimberPostParams['pickup_location'] = $from;
                        $nimberPostParams['delivery_location'] = $to;
                        $nimberPostParams['size'] = $size;
                        $adImageUrl = $this->getEntityManager()->getRepository('FaAdBundle:AdImage')->getImageUrl($adObj, '300X225', 1, $this->container);
                        if ($adImageUrl) {
                            if (!preg_match("~^(?:ht)tps?://~i", $adImageUrl)) {
                                $adImageUrl = str_replace('//', 'http://', $adImageUrl);
                            }
                            $nimberPostParams['picture_location'] = $adImageUrl;
                        }
                        $nimberPostParams['user']['first_name'] = $formParams['first_name'];
                        $nimberPostParams['user']['last_name'] = $formParams['last_name'];
                        $nimberPostParams['user']['email'] = $formParams['email'];
                        $nimberPostParams['user']['mobile'] = $formParams['phone'];
                        $nimberPostParams['user']['country_code'] = 'GB';

                        $taskDetails = $this->container->get('fa.nimber.manager')->createTask($nimberPostParams);
                        if (is_array($taskDetails) && isset($taskDetails['id'])) {
                            $nimberTaskUrl = $taskDetails['url'];
                            $nimberTaskId = $taskDetails['id'];
                            $adNimber->setNimberTaskId($nimberTaskId);
                            $adNimber = $formManager->save($adNimber);
                            $response = new Response();
                            $response->headers->setCookie(new Cookie('nimber_task_'.$adId, 1, time() + 30 * 86400));
                            $response->sendHeaders();
                        } elseif (isset($taskDetails['error'])) {
                            $nimberError = $taskDetails['error'];
                        }
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaAdBundle:Nimber:ajaxNimberCreateTask.html.twig', array('form' => $form->createView(), 'adId' => $adId, 'categoryId' => $adObj->getCategory()->getId()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaAdBundle:Nimber:ajaxNimberCreateTask.html.twig', array('form' => $form->createView(), 'adId' => $adId, 'categoryId' => $adObj->getCategory()->getId()));
                }
            }
            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent, 'nimberError' => $nimberError, 'nimberTaskId' => $nimberTaskId, 'nimberTaskUrl' => $nimberTaskUrl));
        } else {
            return new Response();
        }
    }
}
