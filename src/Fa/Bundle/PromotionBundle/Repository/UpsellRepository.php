<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpsellRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'u';

    const UPSELL_TYPE_ADDITIONAL_PHOTO_ID         = 1;
    const UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE      = 'photo_upsell_value';
    const UPSELL_TYPE_AD_REFRESH_ID               = 2;
    const UPSELL_TYPE_TARGETED_EMAILS_ID          = 3;
    const UPSELL_TYPE_BRANDING_ID                 = 4;
    const UPSELL_TYPE_ACCURATE_VALUATION_ID       = 5;
    const UPSELL_TYPE_SCREENING_QUESTIONS_ID      = 6;
    const UPSELL_TYPE_UNIQUE_REJECTION_LETTERS_ID = 7;
    const UPSELL_TYPE_ATTACH_DOCUMENTS_ID         = 8;
    const UPSELL_TYPE_SOCIAL_INTEGRATION_ID       = 9;
    const UPSELL_TYPE_LOCATION_LOOKUP_ID          = 10;
    const UPSELL_TYPE_VIDEO_ID                    = 11;
    const UPSELL_TYPE_LEADSPRING_ID               = 12;
    const UPSELL_TYPE_PAY_PER_LEAD_MODEL_ID       = 13;
    const UPSELL_TYPE_RECURRING_EVENTS_ID         = 14;
    const UPSELL_TYPE_URGENT_ADVERT_ID            = 15;
    const UPSELL_TYPE_TOP_ADVERT_ID               = 16;
    const UPSELL_TYPE_HOMEPAGE_FEATURE_ADVERT_ID  = 17;
    const UPSELL_TYPE_LISTED_ON_FMG_SITE_ID       = 18;
    const UPSELL_TYPE_PRINT_EDITIONS_ID           = 19;
    const UPSELL_TYPE_PRINT_PHOTO_ID              = 20;
    const UPSELL_TYPE_PRINT_FRAME_ID              = 21;
    const UPSELL_TYPE_DURATION_DEALS_ID           = 22;
    const UPSELL_TYPE_EXPANDED_LOCATION_ID        = 23;

    const SHOP_ENHANCED_PROFILE         = 24;
    const SHOP_VARIFIED_BUSINESS_BADGE  = 25;
    const SHOP_PROFILE_EXPOSURE         = 26;
    const SHOP_ADVERT_EXPOSURE          = 27;
    const SHOP_ITEM_QUANTITIES          = 28;
    const SHOP_FULL_SOCIAL_INTEGRATION  = 29;

    const SHOP_VARIFIED_BUSINESS_BADGE_ID   = 26;
    const SHOP_ITEM_QUANTITIES_ID           = 35;
    const SHOP_PROFILE_EXPOSURE_ID          = 27;
    const SHOP_PROFILE_EXPOSURE_30_MILES_ID = 28;
    const SHOP_PROFILE_EXPOSURE_60_MILES_ID = 29;
    const SHOP_PROFILE_EXPOSURE_NATIONAL_ID = 30;

    const UPSELL_TYPE_JOB_LANDING_PAGE_JOB_OF_WEEK_ID = 31;
    const SHOP_JOB_LANDING_PAGE_FEATURED_EMPLOYER_ID = 32;
    const SHOP_FOR_SALE_LANDING_PAGE_POPULAR_SHOP_ID = 33;
    const SHOP_ADULT_LANDING_PAGE_FEATURED_BUSINESS_ID = 34;

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Add package name filter to existing query object.
     *
     * @param string $title Package title.
     */
    protected function addTitleFilter($title = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.title LIKE \'%%%s%%\'', $this->getRepositoryAlias(), $title));
    }

    /**
     * Add package status filter to existing query object.
     *
     * @param integer $status Status.
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Get entity type array.
     *
     * @param object  $container Container identifier.
     * @param boolean $addEmpty  Flag to show empty message.
     *
     * @return array
     */
    public static function getProfileUpsellTypeArray($container, $addEmpty = true)
    {
        $translator      = CommonManager::getTranslator($container);
        $upsellTypeArray = array();

        $upsellTypeArray[self::SHOP_ENHANCED_PROFILE]        = $translator->trans('Enhanced profile');
        $upsellTypeArray[self::SHOP_PROFILE_EXPOSURE]        = $translator->trans('Profile exposure');
        $upsellTypeArray[self::SHOP_ADVERT_EXPOSURE]         = $translator->trans('Advert exposure');
        $upsellTypeArray[self::SHOP_ITEM_QUANTITIES]         = $translator->trans('Item quantities');
        $upsellTypeArray[self::SHOP_FULL_SOCIAL_INTEGRATION] = $translator->trans('Full social integration');
        $upsellTypeArray[self::SHOP_JOB_LANDING_PAGE_FEATURED_EMPLOYER_ID]  = $translator->trans('Featured employers');
        $upsellTypeArray[self::SHOP_FOR_SALE_LANDING_PAGE_POPULAR_SHOP_ID]  = $translator->trans('Popular shop');
        $upsellTypeArray[self::SHOP_ADULT_LANDING_PAGE_FEATURED_BUSINESS_ID]  = $translator->trans('Featured Adult Business');


        asort($upsellTypeArray);
        if ($addEmpty) {
            $upsellTypeArray =  array('' => $translator->trans('Select Upsell Type')) + $upsellTypeArray;
        }

        return $upsellTypeArray;
    }


    /**
     * Get entity type array.
     *
     * @param object  $container Container identifier.
     * @param boolean $addEmpty  Flag to show empty message.
     *
     * @return array
     */
    public static function getUpsellTypeArray($container, $addEmpty = true)
    {
        $translator      = CommonManager::getTranslator($container);
        $upsellTypeArray = array();

        $upsellTypeArray[self::UPSELL_TYPE_ADDITIONAL_PHOTO_ID]         = $translator->trans('Additional Photos');
        $upsellTypeArray[self::UPSELL_TYPE_AD_REFRESH_ID]               = $translator->trans('Ad Refresh');
        $upsellTypeArray[self::UPSELL_TYPE_TARGETED_EMAILS_ID]          = $translator->trans('Targeted Emails');
        $upsellTypeArray[self::UPSELL_TYPE_BRANDING_ID]                 = $translator->trans('Branding');
        $upsellTypeArray[self::UPSELL_TYPE_ACCURATE_VALUATION_ID]       = $translator->trans('Accurate Valuation');
        $upsellTypeArray[self::UPSELL_TYPE_SCREENING_QUESTIONS_ID]      = $translator->trans('Screening Questions');
        $upsellTypeArray[self::UPSELL_TYPE_UNIQUE_REJECTION_LETTERS_ID] = $translator->trans('Unique Rejection Letters');
        $upsellTypeArray[self::UPSELL_TYPE_ATTACH_DOCUMENTS_ID]         = $translator->trans('Attach Documents');
        $upsellTypeArray[self::UPSELL_TYPE_SOCIAL_INTEGRATION_ID]       = $translator->trans('Social Integration');
        $upsellTypeArray[self::UPSELL_TYPE_LOCATION_LOOKUP_ID]          = $translator->trans('Location Lookup');
        $upsellTypeArray[self::UPSELL_TYPE_VIDEO_ID]                    = $translator->trans('Video');
        $upsellTypeArray[self::UPSELL_TYPE_LEADSPRING_ID]               = $translator->trans('Leadspring Upsells');
        $upsellTypeArray[self::UPSELL_TYPE_PAY_PER_LEAD_MODEL_ID]       = $translator->trans('Pay-per-lead Model');
        $upsellTypeArray[self::UPSELL_TYPE_RECURRING_EVENTS_ID]         = $translator->trans('Recurring Events');
        $upsellTypeArray[self::UPSELL_TYPE_URGENT_ADVERT_ID]            = $translator->trans('Urgent Advert');
        $upsellTypeArray[self::UPSELL_TYPE_TOP_ADVERT_ID]               = $translator->trans('Top Advert');
        $upsellTypeArray[self::UPSELL_TYPE_HOMEPAGE_FEATURE_ADVERT_ID]  = $translator->trans('Homepage Feature Advert');
        $upsellTypeArray[self::UPSELL_TYPE_LISTED_ON_FMG_SITE_ID]       = $translator->trans('Listed On FMG Sites');
        $upsellTypeArray[self::UPSELL_TYPE_PRINT_EDITIONS_ID]           = $translator->trans('Editions');
        $upsellTypeArray[self::UPSELL_TYPE_PRINT_PHOTO_ID]              = $translator->trans('Photo In Print');
        $upsellTypeArray[self::UPSELL_TYPE_PRINT_FRAME_ID]              = $translator->trans('Frame In Print');
        $upsellTypeArray[self::UPSELL_TYPE_DURATION_DEALS_ID]           = $translator->trans('Duration Deals');
        $upsellTypeArray[self::UPSELL_TYPE_EXPANDED_LOCATION_ID]        = $translator->trans('Expanded Location');
        $upsellTypeArray[self::UPSELL_TYPE_JOB_LANDING_PAGE_JOB_OF_WEEK_ID]        = $translator->trans('Job of the week');

        asort($upsellTypeArray);
        if ($addEmpty) {
            $upsellTypeArray =  array('' => $translator->trans('Select Upsell Type')) + $upsellTypeArray;
        }

        return $upsellTypeArray;
    }

    /**
     * Get upsell with given type.
     *
     * @param array   $upsellIds Array of upsell ids.
     * @param integer $type      Upsell type.
     *
     * @return array
     */
    public function getUpsellByType($upsellIds, $type)
    {
        return $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.type = '.$type)
            ->andWhere(self::ALIAS.'.id in(:upsellids)')
            ->setParameter('upsellids', $upsellIds)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get print publication upsell ids array.
     *
     * @return array
     */
    public function getPrintPublicationUpsellIdsArray()
    {
        $printPublicationUpsellIds = array();
        $printUpsells = $this->getBaseQueryBuilder()
            ->andWhere(self::ALIAS.'.type = '.self::UPSELL_TYPE_PRINT_EDITIONS_ID)
            ->getQuery()
            ->getResult();

        foreach ($printUpsells as $printUpsell) {
            $printPublicationUpsellIds[] = $printUpsell->getId();
        }

        return $printPublicationUpsellIds;
    }

    /**
     * Get print upsell ids array.
     *
     * @return array
     */
    public function getPrintUpsellIdsArray()
    {
        $printPublicationUpsellIds = array();
        $printUpsells = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.type IN ('.self::UPSELL_TYPE_PRINT_EDITIONS_ID.','.self::UPSELL_TYPE_PRINT_FRAME_ID.','.self::UPSELL_TYPE_PRINT_PHOTO_ID.')')
        ->getQuery()
        ->getResult();

        foreach ($printUpsells as $printUpsell) {
            $printPublicationUpsellIds[] = $printUpsell->getId();
        }

        return $printPublicationUpsellIds;
    }

    /**
     * Get profile exposure upsell ids array.
     *
     * @return array
     */
    public function getProfileExposureUpsellIdsIdsArray()
    {
        $profileExposureUpsellIds = array();
        $profileExposureUpsells = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.type = '.self::SHOP_PROFILE_EXPOSURE)
        ->getQuery()
        ->getResult();

        foreach ($profileExposureUpsells as $profileExposureUpsell) {
            $profileExposureUpsellIds[] = $profileExposureUpsell->getId();
        }

        return $profileExposureUpsellIds;
    }

    /**
     * Get upsell with given type.
     *
     * @param integer $type      Upsell type.
     *
     * @return array
     */
    public function getUpsellByArrayType($type)
    {
        $upsellArray = array();

        $upsells = $this->getBaseQueryBuilder()
        ->andWhere(self::ALIAS.'.type = '.$type)
        ->getQuery()
        ->getResult();

        foreach ($upsells as $upsell) {
            $upsellArray[$upsell->getId()] = $upsell->getTitle();
        }

        return $upsellArray;
    }
}
