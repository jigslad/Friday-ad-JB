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
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;

/**
 * This controller is used for user address book.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserAddressBookAdminController extends CoreController implements ResourceAuthorizationController
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
            $userId   = $request->get('userId', null);
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
                        $userAddress['zip'] = $postCodeObj->getPostCode();
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
}
