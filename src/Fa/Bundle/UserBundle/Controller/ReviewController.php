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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Entity\UserReview;
use Fa\Bundle\UserBundle\Form\UserReviewType;
use Fa\Bundle\UserBundle\Form\UserReviewResponseType;

/**
 * This controller is used for dashboard home page.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ReviewController extends CoreController
{
    /**
     * List of reviews from buyers or sellers.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function indexAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        // Make tab active based on recent review from sellers or from buyers
        $type = $request->get('type', 'default');
        if ($type == 'default') {
            $isRecentReviewByWhom = $this->getRepository('FaUserBundle:UserReview')->isRecentReviewByWhom($this->getLoggedInUser()->getId());
            $request->attributes->set('type', $isRecentReviewByWhom);
        }

        $reviewFromSellerCount    = $this->getRepository('FaUserBundle:UserReview')->getReviewCount($this->getLoggedInUser()->getId(), 'from_sellers');
        $reviewFromBuyerCount     = $this->getRepository('FaUserBundle:UserReview')->getReviewCount($this->getLoggedInUser()->getId(), 'from_buyers');
        $reviewLeftForOthersCount = $this->getRepository('FaUserBundle:UserReview')->getReviewLeftForOthersCount($this->getLoggedInUser()->getId());

        $type  = $request->get('type', 'from_buyers');
        $query = $this->getRepository('FaUserBundle:UserReview')->getUserReviewsQuery($this->getLoggedInUser()->getId(), $type);

        $this->get('fa.pagination.manager')->init($query, $request->get('page', 1), 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination(false);

        $parameters = array(
                           'reviewFromSellerCount'    => $reviewFromSellerCount,
                           'reviewFromBuyerCount'     => $reviewFromBuyerCount,
                           'reviewLeftForOthersCount' => $reviewLeftForOthersCount,
                           'pagination'               => $pagination
                      );

        return $this->render('FaUserBundle:Review:index.html.twig', $parameters);
    }

    /**
     * List of left for others reviews.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function leftForOthersAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $reviewFromSellerCount    = $this->getRepository('FaUserBundle:UserReview')->getReviewCount($this->getLoggedInUser()->getId(), 'from_sellers');
        $reviewFromBuyerCount     = $this->getRepository('FaUserBundle:UserReview')->getReviewCount($this->getLoggedInUser()->getId(), 'from_buyers');
        $reviewLeftForOthersCount = $this->getRepository('FaUserBundle:UserReview')->getReviewLeftForOthersCount($this->getLoggedInUser()->getId());

        $query = $this->getRepository('FaUserBundle:UserReview')->getUserReviewsLeftForOthersQuery($this->getLoggedInUser()->getId());

        $this->get('fa.pagination.manager')->init($query, $request->get('page', 1), 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination(false);

        $parameters = array(
                           'reviewFromSellerCount'    => $reviewFromSellerCount,
                           'reviewFromBuyerCount'     => $reviewFromBuyerCount,
                           'reviewLeftForOthersCount' => $reviewLeftForOthersCount,
                           'pagination'               => $pagination
                      );

        return $this->render('FaUserBundle:Review:reviewLeftForOthers.html.twig', $parameters);
    }

    /**
     * Add review.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function addAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $formManager = $this->get('fa.formmanager');
        $userReview  = new UserReview();
        $form        = $formManager->createForm(UserReviewType::class, $userReview, array('method' => 'POST'));

        $form->get('ad_id')->setData($request->get('item_id'));
        $form->get('user_id')->setData($request->get('user_id'));
        $form->get('reviewer_id')->setData($request->get('reviewer_id'));

        if ('POST' === $request->getMethod() && $request->get('is_form_load', null) == null) {
            if ($formManager->isValid($form)) {
                $this->handleMessage($this->get('translator')->trans('Review has been added successfully.', array(), 'frontend-review'));
                return new JsonResponse(array('success' => '1', 'htmlContent' => ''));
            } else {
                if ($request->isXmlHttpRequest()) {
                    $htmlContent = $this->renderView('FaUserBundle:Review:leaveReviewForm.html.twig', array('form' => $form->createView()));
                    return new JsonResponse(array('success' => '', 'htmlContent' => $htmlContent));
                }
            }
        }

        $parameters = array('form' => $form->createView());
        return $this->render('FaUserBundle:Review:add.html.twig', $parameters);
    }

    /**
     * Add response to review.
     *
     * @param Request $request A Request object.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function addResponseAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $formManager = $this->get('fa.formmanager');
        $userReview  = new UserReview();
        $form        = $formManager->createForm(UserReviewResponseType::class, $userReview, array('method' => 'POST'));

        $form->get('review_id')->setData($request->get('review_id'));
        $form->get('responder_id')->setData($request->get('responder_id'));

        if ('POST' === $request->getMethod() && $request->get('is_respond_form_load', null) == null) {
            if ($formManager->isValid($form)) {
                $this->handleMessage($this->get('translator')->trans('Review has been responded successfully.', array(), 'frontend-review'));
                return new JsonResponse(array('success' => '1', 'htmlContent' => ''));
            } else {
                if ($request->isXmlHttpRequest()) {
                    $htmlContent = $this->renderView('FaUserBundle:Review:addResponse.html.twig', array('form' => $form->createView()));
                    return new JsonResponse(array('success' => '', 'htmlContent' => $htmlContent));
                }
            }
        }

        $parameters = array('form' => $form->createView());
        return $this->render('FaUserBundle:Review:addResponse.html.twig', $parameters);
    }

    /**
     * Review detail.
     *
     * @param Request $request A Request object.
     * @param integer $id      Review id.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function showAction(Request $request, $id)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $review = $this->getRepository('FaUserBundle:UserReview')->getUserReviewById($id);
        if (!$review) {
            return $this->handleMessage($this->get('translator')->trans('No review exists.', array(), 'frontend-review'), 'user_review_list', array(), 'error');
        }

        return $this->render('FaUserBundle:Review:show.html.twig', array('review' => $review));
    }
}
