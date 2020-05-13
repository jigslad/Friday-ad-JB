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
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\UserBundle\Entity\UserAddressBook;
use Fa\Bundle\UserBundle\Form\UserAddressBookType;

/**
 * This controller is used for user address book.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAddressBookController extends CoreController
{
    /**
     * Get user address using post code.
     *
     * @param Request $request Request object.
     *
     * @return JsonResponse|Response A Response object.
     */
    public function ajaxGetUserAddressAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $userId   = $this->getLoggedInUser()->getId();
            $postCode = $request->get('postCode');

            if ($postCode) {
                $userAddress = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByPostCodeForUser($userId, $postCode);
                // if not found in user address book then get it from post code table.
                if (!$userAddress) {
                    $postCodeObj = $this->getRepository('FaEntityBundle:Postcode')->getPostCodByLocation($postCode);
                    if ($postCodeObj) {
                        $entityCacheManager              = $this->get('fa.entity.cache.manager');
                        $userAddress['street_address']   = '';
                        $userAddress['domicile_name']    = '';
                        $userAddress['town_name']        = '';
                        $userAddress['street_address_2'] = $postCodeObj->getStreet() ? $postCodeObj->getStreet() : '';
                        $userAddress['zip']              = $postCodeObj->getPostCode();
                        if ($postCodeObj->getCountyId()) {
                            $userAddress['domicile_name'] = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $postCodeObj->getCountyId());
                        }
                        if ($postCodeObj->getTownId()) {
                            $userAddress['town_name'] = $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $postCodeObj->getTownId());
                        }
                    }
                }
                if ($userAddress) {
                    $userAddress['errorMsg'] = '';
                } else {
                    $userAddress['errorMsg'] = $this->get('translator')->trans('No matching address found, Please type your address.', array(), 'frontend-user-address-book');
                }
            } else {
                $userAddress['errorMsg'] = $this->get('translator')->trans('Please enter post code.', array(), 'frontend-user-address-book');
            }

            return new JsonResponse($userAddress);
        }

        return new Response();
    }

    /**
     * Add new user address.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxAddAddressAction(Request $request)
    {
        $error           = '';
        $htmlContent     = '';
        $listHtmlContent = '';

        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();
            $userAddressBook = new UserAddressBook();
            $formManager = $this->get('fa.formmanager');
            $form        = $formManager->createForm(UserAddressBookType::class, $userAddressBook);

            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $formManager->save($userAddressBook);
                    $userAddresses = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($loggedinUser->getId(), null, true);
                    $listHtmlContent = $this->renderView('FaUserBundle:MyAccount:listAddress.html.twig', array('userAddresses' => $userAddresses));
                } elseif ($request->isXmlHttpRequest()) {
                    $htmlContent   = $this->renderView('FaUserBundle:UserAddressBook:ajaxNewAddress.html.twig', array('form' => $form->createView(), 'userAddressBook' => $userAddressBook));
                }
            } else {
                $htmlContent = $this->renderView('FaUserBundle:UserAddressBook:ajaxNewAddress.html.twig', array('form' => $form->createView(), 'userAddressBook' => $userAddressBook));
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent, 'listHtmlContent' => $listHtmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Edit user address.
     *
     * @param integer $addressBookId Address book id.
     * @param Request $request       A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxEditAddressAction($addressBookId, Request $request)
    {
        $error           = '';
        $htmlContent     = '';
        $listHtmlContent = '';

        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();
            $userAddressBook = $this->getRepository('FaUserBundle:UserAddressBook')->findOneBy(array('id' => $addressBookId, 'user' => $loggedinUser->getId()));
            if (!$userAddressBook) {
                $error = $this->get('translator')->trans('Invalid address.', array(), 'frontend-user-address-book');
            } else {
                if (!$userAddressBook->getFirstName()) {
                    $userAddressBook->etFirstName($loggedinUser->getFirstName().' '.$loggedinUser->getLastName());
                }
                $formManager = $this->get('fa.formmanager');
                $form        = $formManager->createForm(UserAddressBookType::class, $userAddressBook);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        $formManager->save($userAddressBook);
                        $userAddresses = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($loggedinUser->getId(), null, true);
                        $listHtmlContent = $this->renderView('FaUserBundle:MyAccount:listAddress.html.twig', array('userAddresses' => $userAddresses));
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent   = $this->renderView('FaUserBundle:UserAddressBook:ajaxNewAddress.html.twig', array('form' => $form->createView(), 'userAddressBook' => $userAddressBook));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:UserAddressBook:ajaxNewAddress.html.twig', array('form' => $form->createView(), 'userAddressBook' => $userAddressBook));
                }
            }

            return new JsonResponse(array('error' => $error, 'htmlContent' => $htmlContent, 'listHtmlContent' => $listHtmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Delete user address.
     *
     * @param integer $addressBookId Address book id.
     * @param Request $request       A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxDeleteAddressAction($addressBookId, Request $request)
    {
        $error           = '';
        $listHtmlContent = '';
        $updateInvoiceAddressFlag = false;

        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();
            $userAddressBook = $this->getRepository('FaUserBundle:UserAddressBook')->findOneBy(array('id' => $addressBookId, 'user' => $loggedinUser->getId()));
            if (!$userAddressBook) {
                $error = $this->get('translator')->trans('Invalid address.', array(), 'frontend-user-address-book');
            } else {
                if ($userAddressBook->getIsInvoiceAddress()) {
                    $updateInvoiceAddressFlag = true;
                }
                // initialize form manager service
                $deleteManager = $this->get('fa.deletemanager');
                $deleteManager->delete($userAddressBook);
                if ($updateInvoiceAddressFlag) {
                    $this->getRepository('FaUserBundle:UserAddressBook')->setLatestAddressAsInvoiceAddress($loggedinUser->getId());
                }
                $userAddresses = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($loggedinUser->getId(), null, true);
                $listHtmlContent = $this->renderView('FaUserBundle:MyAccount:listAddress.html.twig', array('userAddresses' => $userAddresses));
            }

            return new JsonResponse(array('error' => $error, 'listHtmlContent' => $listHtmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Update invoice user address.
     *
     * @param integer $addressBookId Address book id.
     * @param Request $request       A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxUpdateInvoiceAddressAction($addressBookId, Request $request)
    {
        $error           = '';
        $listHtmlContent = '';

        if ($this->isAuth() && $request->isXmlHttpRequest()) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $loggedinUser = $this->getLoggedInUser();
            $userAddressBook = $this->getRepository('FaUserBundle:UserAddressBook')->findOneBy(array('id' => $addressBookId, 'user' => $loggedinUser->getId()));
            if (!$userAddressBook) {
                $error = $this->get('translator')->trans('Invalid address.', array(), 'frontend-user-address-book');
            } else {
                $this->getRepository('FaUserBundle:UserAddressBook')->setAddressAsInvoiceAddress($addressBookId, $loggedinUser->getId());
                $userAddresses = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($loggedinUser->getId(), null, true);
                $listHtmlContent = $this->renderView('FaUserBundle:MyAccount:listAddress.html.twig', array('userAddresses' => $userAddresses));
            }

            return new JsonResponse(array('error' => $error, 'listHtmlContent' => $listHtmlContent));
        } else {
            return new Response();
        }
    }
}
