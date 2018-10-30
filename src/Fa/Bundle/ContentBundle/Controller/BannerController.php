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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Fa\Bundle\ContentBundle\Repository\BannerZoneRepository;

/**
 * This controller is used for banner management.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BannerController extends CoreController
{
    /**
     * This action is used to send one click enquiry.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function getBannerByZoneAjaxAction($zoneId, Request $request)
    {
        $bannerCode = '';
        if (!empty($zoneId) && $zoneId > 0) {
            $bannersArray = $this->getRepository("FaContentBundle:Banner")->getBannersArrayByPage('ad_detail_page', $this->container);
            $bannerCode = $this->renderView('FaContentBundle:Banner:show.html.twig', array('zone_id' => $zoneId, 'bannersArray' => $bannersArray));
        }

        return new JsonResponse(array('bannerCode' => $bannerCode));
    }
}
