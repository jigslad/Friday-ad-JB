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
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;

/**
 * This controller is used for static page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class StaticPageController extends CoreController
{

    /**
     * Show action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pix1Action(Request $request)
    {
        if ($request->get('gif')) {
            $id = CommonManager::encryptDecrypt('10101', $request->get('gif'), 'decrypt');
            $emailLog = $this->getHistoryRepository('FaReportBundle:AutomatedEmailReportLog')->find($id);
            if ($emailLog && !$emailLog->getIsOpened()) {
                $emailLog->setIsOpened(1);
                $this->getEntityManager('history')->persist($emailLog);
                $this->getEntityManager('history')->flush();
                $this->getHistoryRepository('FaReportBundle:AutomatedEmailReportDaily')->updateAutomatedEmailReadCounter($emailLog->getIdentifier(), $emailLog->getCreatedAt());
            }
        }
        header('Content-Type: image/jpeg');
        die(hex2bin('89504e470d0a1a0a0000000d494844520000000100000001010300000025db56ca00000003504c5445000000a77a3dda0000000174524e530040e6d8660000000a4944415408d76360000000020001e221bc330000000049454e44ae426082'));
    }

    /**
     * Show action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request)
    {
        $staticPage = $this->getRepository('FaContentBundle:StaticPage')->findOneBy(array('slug' => $request->get('staticpage_slug'), 'type' => StaticPageRepository::STATIC_PAGE_TYPE_ID));

        if (!$staticPage || !$staticPage->getStatus()) {
            throw $this->createNotFoundException($this->get('translator')->trans('Unable to find static page.'));
        }

        $parameters = array(
            'staticPage' => $staticPage,
            'seoFields' => CommonManager::getSeoFields($staticPage, $staticPage->getTitle()),
        );

        return $this->render('FaContentBundle:StaticPage:show.html.twig', $parameters);
    }

    /**
     * Show static pages.
     *
     * @param Request $request
     */
    public function renderStaticPageLinkAction(Request $request)
    {
        $staticPages = $this->getRepository('FaContentBundle:StaticPage')->getStaticPagesForFooter($this->container);
        $parameters  = array('staticPages' => $staticPages, 'currentRoute' => $request->get('currentRoute'));

        return $this->render('FaContentBundle:StaticPage:renderStaticPageLink.html.twig', $parameters);
    }

    /**
     * Show static pages.
     *
     * @param Request $request
     */
    public function renderMobileStaticPageLinkAction(Request $request)
    {
        $mobileStaticPages = $this->getRepository('FaContentBundle:StaticPage')->getStaticPagesForMobileFooter($this->container);
        $parameters  = array('mobileStaticPages' => $mobileStaticPages, 'currentRoute' => $request->get('currentRoute'));

        return $this->render('FaContentBundle:StaticPage:renderMobileStaticPageLink.html.twig', $parameters);
    }
}
