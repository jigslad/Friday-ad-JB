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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This controller is used for admin side category management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class SearchKeywordController extends CoreController
{
    /**
     * Get ajax a category nested nodes in json format.
     *
     * @param Request $request Request instance.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxGetSearchKeywordsJsonAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $keywordArray            = array();
            $keywordArray['more']    = false;
            $keywordArray['results'] = $this->getRepository('FaAdBundle:SearchKeywordCategory')->getKeywordsArrayByText($request->get('term'));

            return new JsonResponse($keywordArray);
        }

        return new Response();
    }
}
