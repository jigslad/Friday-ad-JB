<?php

namespace Fa\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Fa\Bundle\CoreBundle\Repository\ConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ConfigRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'co';

    const PAYPAL_COMMISION      = 1;
    const PRODUCT_INSERTION_FEE = 2;
    const AD_EXPIRATION_DAYS    = 3;
    const LISTING_TOPAD_SLOTS   = 4;
    const PERIOD_BEFORE_CHECKING_VIEWS   = 5;
    const PRECEDING_PERIOD_TO_CHECK_VIEWS = 6;
    const VAT_AMOUNT  = 7;
    const NUMBER_OF_ORGANIC_RESULTS = 8;
    const NUMBER_OF_BUSINESSPAGE_SLOTS = 9;
    const TOP_BUSINESSPAGE = 10;
    const CLICKEDITVEHICLEADVERTS_PACKAGE_ID = 11;
    const LOW_ENQUIERY_LIMIT = 12;
    const LOW_VIEW_LIMIT = 13;
    const PRIVATE_USER_AD_POST_LIMIT = 14;
    const ADZUNA_MOTORS_FEED_USER_IDS = 15;
    const DOTMAILER_ENROLLMENT_PROGRAM_ID = 16;
    const MAPFIT_API_KEY_ID = 17;
    const LIMIT_SPONSORED_ADS = 18;

    const DEFAULT_LOW_ENQUIERY_LIMIT = 10;
    const DEFAULT_LOW_VIEW_LIMIT = 100;
    const DEFAULT_EXPIRATION_DAYS = 28;
    const DEFAULT_LISTING_TOPAD_SLOTS = 5;
    const DEFAULT_PERIOD_BEFORE_CHECKING_VIEWS   = 90;
    const DEFAULT_PRECEDING_PERIOD_TO_CHECK_VIEWS = 30;
    const DEFAULT_VAT_AMOUNT  = 20;
    const DEFAULT_NUMBER_OF_BUSINESSPAGE_SLOTS = 3;
    const LIVE_CAMS_URL = 'https://engine.voluumtlkrnarketing.com/?611886259';
    const LOCAL_DATING_URL = 'https://reactads.engine.adglare.net/?933883370';
    const SUGAR_BABIES_URL = 'https://engine.voluumtlkrnarketing.com/?820533264';


    public function getRuleArray()
    {
        return array(
            self::PAYPAL_COMMISION                => 'PayPal commission',
            self::PRODUCT_INSERTION_FEE           => 'Product Insertion Fee',
            self::AD_EXPIRATION_DAYS              => 'Ad Expiration Days',
            self::LISTING_TOPAD_SLOTS             => 'Listing top ad slots',
            self::PERIOD_BEFORE_CHECKING_VIEWS    => 'Period(in days) before checking views(Move expired to archive)',
            self::PRECEDING_PERIOD_TO_CHECK_VIEWS => 'Preceding period(in days) to check views(Move expired to archive)',
            self::VAT_AMOUNT                      => 'Vat amount',
            self::NUMBER_OF_ORGANIC_RESULTS       => 'Number of organic results',
            self::NUMBER_OF_BUSINESSPAGE_SLOTS    => 'Number of business page slots',
            self::TOP_BUSINESSPAGE                => 'Top business page',
            self::LOW_VIEW_LIMIT                  => 'Low view limit',
            self::LOW_ENQUIERY_LIMIT              => 'Low enquiry limit',
            self::CLICKEDITVEHICLEADVERTS_PACKAGE_ID => 'Clickeditvehicleadverts package id',
            self::PRIVATE_USER_AD_POST_LIMIT => 'Private user ad post limit',
            self::ADZUNA_MOTORS_FEED_USER_IDS => 'Adzuna motors feed user ids',
            self::DOTMAILER_ENROLLMENT_PROGRAM_ID => 'Dotmailer enrollment program id',
            self::MAPFIT_API_KEY_ID				  => 'MapFit Key',
            SELF::LIMIT_SPONSORED_ADS             => 'LIMIT_SPONSORED_ADS',
        );
    }

    /**
     * Get period(in days) before checking views(Move expired to archive.
     *
     * @param integer $categoryId Category id.
     *
     * @return integer
     */
    public function getPeriodBeforeCheckingViewsForMoveExpiredAdsToArvhice($categoryId = null)
    {
        $configRule = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getActiveConfigRulesByCategoryAncestor(self::PERIOD_BEFORE_CHECKING_VIEWS, null, 1);

        if ($configRule) {
            return $configRule[0]->getValue();
        } else {
            return self::DEFAULT_PERIOD_BEFORE_CHECKING_VIEWS;
        }
    }


    /**
     * Get preceding period(in days) to check views(Move expired to archive).
     *
     * @param integer $categoryId Category id.
     *
     * @return integer
     */
    public function getPrecedingPeriodToCheckViewsForMoveExpiredAdsToArvhice($categoryId = null)
    {
        $configRule = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getActiveConfigRulesByCategoryAncestor(self::PRECEDING_PERIOD_TO_CHECK_VIEWS, null, 1);

        if ($configRule) {
            return $configRule[0]->getValue();
        } else {
            return self::DEFAULT_PRECEDING_PERIOD_TO_CHECK_VIEWS;
        }
    }

    /**
     * Get vat amount.
     *
     * @return integer
     */
    public function getVatAmount()
    {
        $configRule = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getActiveConfigRulesByCategoryId(self::VAT_AMOUNT, null, 1);

        if ($configRule) {
            return $configRule[0]->getValue();
        } else {
            return self::DEFAULT_VAT_AMOUNT;
        }
    }
    public function getSponsoredLimit(){

        $configRule = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getActiveConfigRulesByCategoryId(self::LIMIT_SPONSORED_ADS, null, 1);

        if ($configRule) {
            return (int)$configRule[0]->getValue();
        } else {
            return 0;
        }

    }
}
