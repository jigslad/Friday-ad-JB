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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\UserBundle\Entity\Competition;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Form\CompetitionType;

/**
 * This controller is used for user competition.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class CompetitionController extends CoreController
{
    /**
     * enter into competition.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxEnterInCompetitionAction(Request $request)
    {
        $success       = '';
        $error         = '';
        $htmlContent   = '';

        if ($request->isXmlHttpRequest() && $this->isAuth()) {
            $transactionId = $request->get('transaction_id');
            $loggedinUser = $this->getLoggedInUser();
            $competitionObj = $this->getRepository('FaUserBundle:Competition')->findOneBy(array('user' => $loggedinUser->getId()));
            if (!$competitionObj) {
                $adDetailArray = array();
                $payment = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('cart_code' => $transactionId, 'status' => 1, 'user' => $loggedinUser->getId()));
                if ($payment) {
                    $adDetailArray = $this->getRepository('FaPaymentBundle:PaymentTransaction')->getAdDetailByCartCode($payment->getCartCode(), $this->container);
                }

                if ($payment && count($adDetailArray)) {
                    $formManager  = $this->get('fa.formmanager');
                    $competition  = new Competition();
                    $competition->setCartCode($transactionId);
                    $competition->setCompetitionType($this->getEntityManager()->getReference('FaEntityBundle:Entity', EntityRepository::COMPETITION_TYPE_ID));
                    $form         = $formManager->createForm(CompetitionType::class, $competition);
                    if ('POST' === $request->getMethod()) {
                        $form->handleRequest($request);

                        if ($form->isValid()) {
                            //save information
                            $competition = $formManager->save($competition);
                            $success = 'sucess';
                        } elseif ($request->isXmlHttpRequest()) {
                            $htmlContent = $this->renderView('FaUserBundle:Competition:ajaxEnterCompetitionPopup.html.twig', array('form' => $form->createView(), 'transactionId' => $transactionId));
                        }
                    } else {
                        $htmlContent = $this->renderView('FaUserBundle:Competition:ajaxEnterCompetitionPopup.html.twig', array('form' => $form->createView(), 'transactionId' => $transactionId));
                    }
                }

                return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent, 'success' => $success));
            } else {
                $error = $this->get('translator')->trans('You already subscribed for competition.', array(), 'frontend-competition');
                return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent, 'success' => $success));
            }
        } else {
            return new Response();
        }
    }

    /**
     * leave competition.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxLeaveCompetitionAction(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isAuth()) {
            $transactionId = $request->get('transaction_id');
            $loggedinUser = $this->getLoggedInUser();
            $competitionObj = $this->getRepository('FaUserBundle:Competition')->findOneBy(array('user' => $loggedinUser->getId()));
            if (!$competitionObj) {
                $payment = $this->getRepository('FaPaymentBundle:Payment')->findOneBy(array('cart_code' => $transactionId, 'status' => 1, 'user' => $loggedinUser->getId()));

                if ($payment) {
                    $competition  = new Competition();
                    $competition->setUser($loggedinUser);
                    $competition->setCartCode($transactionId);
                    $competition->setCompetitionType($this->getEntityManager()->getReference('FaEntityBundle:Entity', EntityRepository::COMPETITION_TYPE_ID));
                    $competition->setStatus(0);
                    $this->getEntityManager()->persist($competition);
                    $this->getEntityManager()->flush($competition);
                }
            }
            return new Response();
        } else {
            return new Response();
        }
    }
}
