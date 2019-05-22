<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Controller;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\CoreBundle\Manager\FormManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\AdBundle\Repository\AdImageRepository;
use Fa\Bundle\ReportBundle\Form\PoliceReportSearchAdminType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;

/**
 * This is default controller for dot mailer bundle.
 *
 * @author    Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version   v1.0
 */
class PoliceReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Police report action.
     *
     * @param Request $request A Request object.
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        /**
         * @var FormManager $formManager
         * @var Ad          $objSearchAd
         * @var Ad[]        $objAds
         * @var User        $objUser
         */
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:Ad'), $this->getRepositoryTable('FaAdBundle:Ad'), 'fa_police_report');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(PoliceReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_police'), 'method' => 'GET'));
        $reportData = array();

        if ($data['search']) {
            $data['search'] = array_filter($data['search']);
            $form->submit($data['search']);
            if ($form->isValid()) {
                if ($data && isset($data['search']['ad_id'])) { //Search by ad ref
                    $objSearchAd = $this->getRepository('FaAdBundle:Ad')->find($data['search']['ad_id']);
                    if (!$objSearchAd) {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry no advert found.'), 'error');
                        return $this->redirectToRoute('fa_report_police');
                    }
                    $userId = $objSearchAd->getUser()->getId();
                } elseif ($data && isset($data['search']['email'])) { //Search by email
                    $objUser = $this->getRepository('FaUserBundle:User')->findOneByEmail($data['search']['email']);
                    if (!$objUser) {
                        $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Sorry no user found.'), 'error');
                        return $this->redirectToRoute('fa_report_police');
                    }
                    $userId = $objUser->getId();
                }

                //General account info & Business details
                $userDetails = $this->getRepository('FaUserBundle:User')->getUserDetailsById($userId);
                if ($userDetails && is_array($userDetails)) {
                    $reportData['user_details'] = $userDetails;
                }

                //Saved addresses (General account info)
                $userAddresses = $this->getRepository('FaUserBundle:UserAddressBook')->getAddressByUser($userId);
                if ($userAddresses && is_array($userAddresses)) {
                    $reportData['user_addresses'] = $userAddresses;
                }

                //Account purchases
                $accountPurchases = $this->getRepository('FaUserBundle:UserPackage')->getAccountPurchases($userId);
                if ($accountPurchases && is_array($accountPurchases)) {
                    $reportData['account_purchases'] = $accountPurchases;
                }

                //Advert info
                if ($data && isset($data['search']['include_ads'])) {
                    if ($data && isset($data['search']['ad_id'])) { //Search by ad ref
                        $objAds[] = $objSearchAd;
                    } elseif ($data && isset($data['search']['email'])) { //Search by email
                        $objAds = $this->getRepository('FaAdBundle:Ad')->findByUser($userId);
                    }


                    if ($objAds) {
                        foreach ($objAds as $key => $objAd) {
                            $categoryId = $objAd->getCategory()->getId();
                            $adImages = $this->getAdImages($objAd);
                            $categoryPath = $this->getCategoryPath($categoryId);
                            $adDimensions = $this->getAdDimensions($objAd, $categoryId);
                            $advertPackages = $this->getRepository('FaAdBundle:AdUserPackage')->getAdvertPackagePurchases($objAd->getId());

                            $activityParams['ad_id'] = $objAd->getId();
                            $activityParams['report_columns'] = array('ad_created_at', 'ad_id', 'edited_at', 'expired_at', 'expires_at', 'is_edit', 'is_expired', 'is_renewed', 'renewed_at', 'status_id', 'ip_addresses');
                            $activitySorter['sort_field'] = 'edited_at';
                            $activitySorter['sort_ord'] = 'ASC';
                            $activityQuery = $this->getHistoryRepository('FaReportBundle:AdReportDaily')->getAdReportQuery($activityParams, $activitySorter, $this->container, false);
                            $advertActivityResult = $activityQuery->execute();

                            $reportData['user_adverts'][] = array(
                                'id' => $objAd->getId(),
                                'title' => $objAd->getTitle(),
                                'description' => $objAd->getDescription(),
                                'category_id' => $categoryId,
                                'images' => $adImages,
                                'category_path' => $categoryPath,
                                'dimensions' => $adDimensions,
                                'purchases' => $advertPackages,
                                'activities' => $advertActivityResult,
                            );
                        }
                    }
                }

                //Messages
                if ($data && isset($data['search']['include_messages'])) {
                    if ($data && isset($data['search']['ad_id'])) { //Search by ad ref
                        $messagesArray = $this->getRepository('FaMessageBundle:Message')->getMessagesForPoliceReport($objSearchAd->getId(), 'ad');
                    }
                    if ($data && isset($data['search']['email'])) { //Search by email
                        $messagesArray = $this->getRepository('FaMessageBundle:Message')->getMessagesForPoliceReport($userId, 'user');
                    }
                    $reportData['user_messages'] = $messagesArray;
                }
            }
        }

        $parameters = array(
            'heading' => 'Police report',
            'form' => $form->createView(),
            'searchParams' => $data['search'],
            'reportData' => $reportData,
        );

        return $this->render('FaReportBundle:PoliceReportAdmin:index.html.twig', $parameters);
    }

    /**
     * @param Ad $objAd
     * @return array
     */
    public function getAdImages($objAd)
    {
        /**
         * @var AdImageRepository $objRepoAdImage
         */
        $adImages = array();
        $objRepoAdImage = $this->getRepository('FaAdBundle:AdImage');
        $objAdImages = $objRepoAdImage->getAdImages($objAd->getId());
        if ($objAdImages && count($objAdImages) > 0) {
            foreach ($objAdImages as $key => $objAdImage) {
                if ($objAdImage) {
                    $imageUrl = CommonManager::getAdImageUrl($this->container, $objAd->getId(), $objAdImage->getPath(), $objAdImage->getHash(), null, $objAdImage->getAws(), $objAdImage->getImageName());
                    if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                        $imageUrl = str_replace('//', 'http://', $imageUrl);
                    }

                    $adImages[] = $imageUrl;
                }
            }
        }

        return $adImages;
    }

    /**
     * @param $categoryId
     * @return string
     */
    public function getCategoryPath($categoryId)
    {
        $categoryPath = '';
        $categoryPathArray = $this->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
        if ($categoryPathArray && count($categoryPathArray) > 0) {
            $categoryPath = implode(' > ', $categoryPathArray);
        }

        return $categoryPath;
    }

    /**
     * @param $objAd
     * @param $categoryId
     * @return array
     */
    public function getAdDimensions($objAd, $categoryId)
    {
        $adDimensions = array();
        $rootCategoryId = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        $className = CommonManager::getCategoryClassNameById($rootCategoryId, true);
        $repository = null;
        if ($className) {
            $repository = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:' . 'Ad' . $className);
        }

        $object = null;
        if ($repository) {
            $object = $repository->findOneBy(array('ad' => $objAd->getId()));
        }

        $key = 0;
        if ($object) {
            $metaData = ($object->getMetaData() ? unserialize($object->getMetaData()) : null);
            $paaFields = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($categoryId, $this->container);
            foreach ($paaFields as $field => $label) {
                $value = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($field, $object, $metaData, $this->container, $className);
                if ($value != null) {
                    $adDimensions[] = array($label => $value);
                    $key++;
                }
            }
        }

        return $adDimensions;
    }

}
