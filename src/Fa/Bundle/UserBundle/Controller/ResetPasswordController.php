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

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\UserBundle\Form\ResetPasswordType;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Event\UserEvents;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for resetting user password.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class ResetPasswordController extends CoreController
{
    /**
     * Send reset password link to user.
     *
     * @param integer $id User id.
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function sendResetLinkAction(Request $request, $id)
    {
        $encryption_key = $this->container->getParameter('reset_password_encryption_key');
        $entity = $this->getRepository('FaUserBundle:User')->find($id);

        $resetPasswordLink = $this->generateUrl('reset_password', array('id' => CommonManager::encryptDecrypt($encryption_key, $id), 'key' => $entity->getEncryptedKey(), 'mail_time' => CommonManager::encryptDecrypt($encryption_key, time())), true);
        $this->get('fa.mail.manager')->send($entity->getEmail(), 'reset_password_link', array('user_first_name' => $entity->getFirstName(), 'user_last_name' => $entity->getLastName(), 'user_email_address' => $entity->getEmail(), 'url_password_reset' => $resetPasswordLink), CommonManager::getCurrentCulture($this->container));

        $messageManager = $this->get('fa.message.manager');
        $messageManager->setFlashMessage("A mail with reset password link has been sent to user.", 'success');

        return $this->redirect($request->server->get('HTTP_REFERER'));
    }

    /**
     * Reset password.
     *
     * @param Request $request   Request instance.
     * @param Integer $id        User id.
     * @param String  $key       Hash key.
     * @param String  $mail_time Encrypted time when mail sent.
     *
     * @return Response|RedirectResponse A Response or RedirectResponse object.
     */
    public function resetPasswordAction(Request $request, $id, $key, $mail_time)
    {
        $current_time   = time();
        $encryption_key = $this->container->getParameter('reset_password_encryption_key');
        $id             = CommonManager::encryptDecrypt($encryption_key, $id, 'decrypt');
        $mailed_time    = CommonManager::encryptDecrypt($encryption_key, $mail_time, 'decrypt');
        $diff_in_hrs    = floor(($current_time - $mailed_time)/3600);

        if ($diff_in_hrs >= 3 || $diff_in_hrs < 0) {
            return $this->handleMessage($this->get('translator')->trans('Your reset password link is expired.'), 'fa_frontend_homepage', array(), 'error');
        }

        $entity = $this->getRepository('FaUserBundle:User')->find($id);

        if (!$entity) {
            return $this->handleMessage($this->get('translator')->trans('No user exists.'), 'fa_frontend_homepage');
        }

        if ($key != $entity->getEncryptedKey()) {
            return $this->handleMessage($this->get('translator')->trans('You are not authorised to access this page.'), 'fa_frontend_homepage');
        } else {
            $formManager = $this->get('fa.formmanager');
            $form       = $formManager->createForm(ResetPasswordType::class, $entity);

            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $formManager->save($entity);
                    $this->get('fa.mail.manager')->send($entity->getEmail(), 'password_reset_confirmation', array('user_first_name' => $entity->getFirstName(), 'user_last_name' => $entity->getLastName(), 'user_email_address' => $entity->getEmail()), CommonManager::getCurrentCulture($this->container));
                    return $this->handleMessage($this->get('translator')->trans('You password has been reset successfully.'), 'fa_frontend_homepage');
                }
            }

            return $this->render(
                'FaUserBundle:ResetPassword:resetpassword.html.twig',
                array(
                    'form'   => $form->createView(),
                )
            );
        }
    }
}
