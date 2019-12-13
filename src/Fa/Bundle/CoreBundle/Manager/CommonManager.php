<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PromotionBundle\Repository\PackageDiscountCodeRepository;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * This manager is used to handle common functionalities.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 * @version v1.0
 */
class CommonManager
{
    const COOKIE_DELETED = 'deleted';
    public static $SeoHardCodeForUrl = ['/bristol/', '/bristol/motors/cars/', '/bristol/for-sale/free-to-collector/', '/swindon/', '/bristol/animals/pets/cats-kittens/', '/bristol/animals/pets/', '/gloucester/', '/bristol/animals/pets/dogs-puppies/', '/bristol/adult/'];

    /**
     * Converts and returns timestamp from given start date string.
     *
     * @param string $date Date in string format.
     *
     * @return integer
     */
    public static function getTimeStampFromStartDate($date)
    {
        if (preg_match('/^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/', $date)) {
            $dateArr = explode('/', $date);
            $date    = implode('-', array_reverse($dateArr));

            return strtotime($date);
        } elseif (preg_match('/^(\d{2}|\d{4})-\d{1,2}-\d{1,2}$/', $date)) {
            return strtotime($date);
        }

        return null;
    }

    /**
     * Converts and returns timestamp from given end date string.
     *
     * @param string $date Date in string format.
     *
     * @return integer
     */
    public static function getTimeStampFromEndDate($date)
    {
        $date = self::getTimeStampFromStartDate($date);

        //add 23 hour, 59 min, 59 sec for to comparison
        if ($date) {
            return ($date + 86399);
        }

        return null;
    }

    /**
     * Get translator using continer.
     *
     * @param object $container Container identifier.
     *
     * @return Symfony\Component\Translation\Translator Translator object.
     */
    public static function getTranslator($container)
    {
        /*
        try {
            $locale = $container->get('request_stack')->getCurrentRequest()->getLocale();
        } catch (\Exception $e) {
            $locale = $container->getParameter('locale');
        }
        return new Translator($locale, new MessageSelector());
        */

        return $container->get('translator');
    }

    /**
     * Get currency code using container.
     *
     * @param object $container Container identifier
     *
     * @return string
     */
    public static function getCurrencyCode($container)
    {
        if ($container->get('request_stack')->getCurrentRequest()) {
            $locale = $container->get('request_stack')->getCurrentRequest()->getLocale();
        } else {
            $locale = $container->getParameter('locale');
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        return $formatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE);
    }

    /**
     * Get currency symbol.
     *
     * @param string $locale    Locale.
     * @param object $container Container identifier
     *
     * @return string
     */
    public static function getCurrencySymbol($locale, $container)
    {
        if (!$locale) {
            if ($container->get('request_stack')->getCurrentRequest()) {
                $locale = $container->get('request_stack')->getCurrentRequest()->getLocale();
            } else {
                $locale = $container->getParameter('locale');
            }
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);

        return $symbol;
    }

    /**
     * Get encrypted/decrypted string.
     *
     * @param string $key    Key used in encryption.
     * @param string $string String to be encrypted or decrypted.
     * @param string $action String which decided mode of action.
     *
     * @return string
     */
    public static function encryptDecrypt($key, $string, $action = 'encrypt')
    {
        $res = '';
        if ($action !== 'encrypt') {
            $string = base64_decode($string);
        }

        for ($i = 0; $i < strlen($string); $i++) {
            $c = ord(substr($string, $i));
            if ($action == 'encrypt') {
                $c += ord(substr($key, (($i + 1) % strlen($key))));
                $res .= chr($c & 0xFF);
            } else {
                $c -= ord(substr($key, (($i + 1) % strlen($key))));
                $res .= chr(abs($c) & 0xFF);
            }
        }

        if ($action == 'encrypt') {
            $res = base64_encode($res);
        }
        return $res;
    }

    /**
     * Set cache value.
     *
     * @param object  $container Container identifier.
     * @param string  $key       Cache key.
     * @param mix     $val       Cache value.
     * @param integer $lifetime  Cache life time.
     *
     * @return boolean
     */
    public static function setCacheVersion($container, $key, $val, $lifetime = null)
    {
        //set cache
        //return $container->get('fa.cache.manager')->set($key, serialize($val), $lifetime);
        if ($container->hasParameter('redis_use_set') && $container->getParameter('redis_use_set')) {
            $setArray = explode('|', $key);
            if (is_array($setArray) && isset($setArray[0]) && isset($setArray[1])) {
                $set = $setArray[0]."|".$setArray[1];
                return $container->get('fa.cache.manager')->hSet($set, $key, serialize($val), $lifetime);
            } else {
                return $container->get('fa.cache.manager')->set($key, serialize($val), $lifetime);
            }
        } else {
            return $container->get('fa.cache.manager')->set($key, serialize($val), $lifetime);
        }
    }

    /**
     * Returns value from cache using key.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     *
     * @return mixed
     */
    public static function getCacheVersion($container, $key)
    {
        if ($container->hasParameter('redis_use_set') && $container->getParameter('redis_use_set')) {
            $setArray = explode('|', $key);
            if (is_array($setArray) && isset($setArray[0]) && isset($setArray[1])) {
                $set = $setArray[0]."|".$setArray[1];
                $cacheValue = $container->get('fa.cache.manager')->hGet($set, $key);
            } else {
                $cacheValue = $container->get('fa.cache.manager')->get($key, null);
            }
        } else {
            $cacheValue = $container->get('fa.cache.manager')->get($key, null);
        }

        return $cacheValue === false ? $cacheValue : unserialize($cacheValue);
    }

    /**
     * Remove value from cache using key.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     *
     * @return mixed
     */
    public static function removeCachePattern($container, $key)
    {
        if ($container->hasParameter('redis_use_set') && $container->getParameter('redis_use_set')) {
            $tempKey = str_replace('*', '', $key);
            $setArray = explode('|', $tempKey);
            if (is_array($setArray) && isset($setArray[0]) && isset($setArray[1])) {
                $set = $setArray[0]."|".$setArray[1];
                return $container->get('fa.cache.manager')->removeFromHashByPattern($set, $key);
            } else {
                return $container->get('fa.cache.manager')->removePattern($key);
            }
        } else {
            return $container->get('fa.cache.manager')->removePattern($key);
        }
    }

    /**
     * Remove value from cache using key.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     *
     * @return mixed
     */
    public static function removeCache($container, $key)
    {
        return $container->get('fa.cache.manager')->delete($key);
    }

    /**
     * Remove value from cache using key using zDelete.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     * @param mix    $val       Cache value.
     *
     * @return mixed
     */
    public static function removeCacheUsingZDelete($container, $key, $value)
    {
        return $container->get('fa.cache.manager')->zDelete($key, $value);
    }

    /**
     * Get cache counter for given key using zIncr.
     *
     * @param object  $container Container identifier.
     * @param string  $key       Cache key.
     * @param integer $start     Start.
     * @param integer $limit     Limit.
     *
     * @return mixed
     */
    public static function removeCacheUsingZDeleteRangeByRank($container, $key, $start, $limit)
    {
        return $container->get('fa.cache.manager')->zDeleteRangeByRank($key, $start, $limit);
    }

    /**
     * Update cache counter for given key.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     *
     * @return mixed
     */
    public static function updateCacheCounter($container, $key)
    {
        return $container->get('fa.cache.manager')->incr($key);
    }

    /**
     * Update cache counter for given key by zIncrBy.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     * @param string $value     Value of key.
     *
     * @return mixed
     */
    public static function updateCacheCounterUsingZIncr($container, $key, $value)
    {
        return $container->get('fa.cache.manager')->zIncrBy($key, 1, $value);
    }

    /**
     * Get cache counter for given key.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     *
     * @return mixed
     */
    public static function getCacheCounter($container, $key)
    {
        return $container->get('fa.cache.manager')->getSimpleKey($key);
    }

    /**
     * Returns the cardinality of an ordered set.
     *
     * @param object $container Container identifier.
     * @param string $key       Cache key.
     *
     * @return integer
     */
    public static function getCacheZSize($container, $key)
    {
        return $container->get('fa.cache.manager')->zSize($key);
    }

    /**
     * Get cache counter for given key using zIncr.
     *
     * @param object  $container Container identifier.
     * @param string  $key       Cache key.
     * @param integer $start     Start.
     * @param integer $limit     Limit.
     *
     * @return array
     */
    public static function getCacheCounterUsingZIncr($container, $key, $start, $limit)
    {
        return $container->get('fa.cache.manager')->zRange($key, $start, $limit, true);
    }

    /**
     * Get current culture using container.
     *
     * @param object $container Container identifier.
     *
     * @return string
     */
    public static function getCurrentCulture($container)
    {
        try {
            if (!empty($container->get('request_stack')->getCurrentRequest())) {
                $locale = $container->get('request_stack')->getCurrentRequest()->getLocale();
            } else {
                $locale = $container->getParameter('locale');
            }
        } catch (\Exception $e) {
            $locale = $container->getParameter('locale');
        }
        return $locale;
    }

    /**
     * Get days of week array.
     *
     * @param Container $container Container identifier.
     *
     * @return array
     */
    public static function getDaysOfWeekArray($container = null)
    {
        if ($container) {
            $translator      = CommonManager::getTranslator($container);
            $daysOfWeekArray = array(
                '1' => $translator->trans('Monday'),
                '2' => $translator->trans('Tuesday'),
                '3' => $translator->trans('Wednesday'),
                '4' => $translator->trans('Thursday'),
                '5' => $translator->trans('Friday'),
                '6' => $translator->trans('Saturday'),
                '7' => $translator->trans('Sunday'),
            );
        } else {
            $daysOfWeekArray = array(
                '1' => 'Monday',
                '2' => 'Tuesday',
                '3' => 'Wednesday',
                '4' => 'Thursday',
                '5' => 'Friday',
                '6' => 'Saturday',
                '7' => 'Sunday',
            );
        }

        return $daysOfWeekArray;
    }

    /**
     * Get time of day in intervals.
     *
     * @param integer $interval Time interval.
     *
     * @return array
     */
    public static function getTimeWithIntervalArray($interval = 1)
    {
        $timeArray = array();
        $midnight  = array();
        for ($t = $interval; $t <= 1440; $t += $interval) {
            $timeVal  = sprintf("%02d:%02d", (($t / 60) % 24), ($t % 60));
            $timeText = date("h:i A", mktime((($t / 60) % 24), ($t % 60), 0, 0, 0, 0));
            if ($t == 1440) {
                $midnight['23:59'] = $timeText;
            } else {
                $timeArray[$timeVal] = $timeText;
            }
        }

        $timeArray = $midnight + $timeArray;

        return $timeArray;
    }

    /**
     * Get time of day in intervals.
     *
     * @param integer $interval Time interval.
     *
     * @return array
     */
    public static function getTimeWithIntervalArray1($interval = 1)
    {
        $timeArray = array();
        $midnight  = array();
        for ($t = $interval; $t <= 1440; $t += $interval) {
            $timeVal  = sprintf("%02d:%02d", (($t / 60) % 24), ($t % 60));
            if ($t == 1440) {
                $midnight[] = '00:00';
            } else {
                $timeArray[] = $timeVal;
            }
        }

        $timeArray = array_merge($midnight, $timeArray);

        return $timeArray;
    }

    /**
     * Validate twig content.
     *
     * @param object $container Container identifier.
     * @param string $value     String to be validate.
     *
     * @return string
     */
    public static function validateTwigContent($container, $value)
    {
        $message = '';
        $twig    = $container->get('twig');

        try {
//             $twig->parse($twig->tokenize($value)); // Twig_Environment::tokenize() must be an instance of Twig_Source
            $twig->parse($twig->tokenize(new \Twig_Source($value, \Twig_Token::STRING_TYPE)));
        } catch (\Twig_Error_Syntax $e) {
            $message = $e->getMessage();
        }

        return $message;
    }

    /**
     * Get global email template variables.
     *
     * @return array
     */
    public static function getGlobalEmailTemplateVariables()
    {
        $variableArray = array('{{ site_url }}', '{{ service }}', '{{support_phone_number}}', '{{business_support_phone}}', '{{date_timestamp}}', '{{user_email}}', '{{template_name}}', '{{site_logo_url}}');

        return $variableArray;
    }

    /**
     * Return hash of size 16 or 32
     *
     * @param integer $is32 Numeric value.
     *
     * @return string
     */
    public static function generateHash($is32 = 0)
    {
        $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '192.168.100.31';
        if (!$is32) {
            $hash = substr(md5(md5(time().rand().$remoteAddress.microtime()).time()), 0, 16);
        } else {
            $hash = substr(md5(md5(time().rand().$remoteAddress.microtime()).time()), 0, 32);
        }

        return $hash;
    }

    /**
     * Return seo array based on passed object or array.
     *
     * @param mixed  $entity             Entity array or object.
     * @param string $defaultEntityValue Default name or title of entity.
     *
     * @return array
     */
    public static function getSeoFields($entity, $defaultEntityValue = null)
    {
        $seoFieldArray = array();
        if (is_object($entity)) {
            //page title
            if ($entity->getPageTitle()) {
                $seoFieldArray['page_title'] = $entity->getPageTitle();
            } elseif ($defaultEntityValue) {
                $seoFieldArray['page_title'] = $defaultEntityValue;
            }
            if ($seoFieldArray['page_title']!='') {
                $seoFieldArray['page_title'] = str_replace(' in UK', '', $seoFieldArray['page_title']);
            }
            //h1 tag
            if ($entity->getH1Tag()) {
                $seoFieldArray['h1_tag'] = $entity->getH1Tag();
            } elseif ($defaultEntityValue) {
                $seoFieldArray['h1_tag'] = $seoFieldArray['page_title'];
            }
            
            //meta description
            if ($entity->getMetaDescription()) {
                $seoFieldArray['meta_description'] = $entity->getMetaDescription();
                if ($seoFieldArray['meta_description']!='') {
                    $seoFieldArray['meta_description'] = str_replace(' in UK', '', $seoFieldArray['meta_description']);
                }
            }
            //meta description
            if ($entity->getMetaKeywords()) {
                $seoFieldArray['meta_keywords'] = $entity->getMetaKeywords();
            }

            //image alt
            if (method_exists($entity, 'getImageAlt') && $entity->getImageAlt()) {
                $seoFieldArray['image_alt'] = $entity->getImageAlt();
            }
            if (method_exists($entity, 'getImageAlt2') && $entity->getImageAlt2()) {
                $seoFieldArray['image_alt_2'] = $entity->getImageAlt2();
            }
            if (method_exists($entity, 'getImageAlt3') && $entity->getImageAlt3()) {
                $seoFieldArray['image_alt_3'] = $entity->getImageAlt3();
            }
            if (method_exists($entity, 'getImageAlt_4') && $entity->getImageAlt4()) {
                $seoFieldArray['image_alt_4'] = $entity->getImageAlt();
            }
            if (method_exists($entity, 'getImageAlt5') && $entity->getImageAlt5()) {
                $seoFieldArray['image_alt_5'] = $entity->getImageAlt5();
            }
            if (method_exists($entity, 'getImageAlt6') && $entity->getImageAlt6()) {
                $seoFieldArray['image_alt_6'] = $entity->getImageAlt6();
            }
            if (method_exists($entity, 'getImageAlt7') && $entity->getImageAlt7()) {
                $seoFieldArray['image_alt_7'] = $entity->getImageAlt7();
            }
            if (method_exists($entity, 'getImageAlt8') && $entity->getImageAlt8()) {
                $seoFieldArray['image_alt_8'] = $entity->getImageAlt8();
            }

            //meta robots
            if ($entity->getNoIndex() && $entity->getNoFollow()) {
                $seoFieldArray['meta_robots'] = 'noindex, nofollow';
            } elseif (!$entity->getNoIndex() && $entity->getNoFollow()) {
                $seoFieldArray['meta_robots'] = 'index, nofollow';
            } elseif ($entity->getNoIndex() && !$entity->getNoFollow()) {
                $seoFieldArray['meta_robots'] = 'noindex, follow';
            } elseif (!$entity->getNoIndex() && !$entity->getNoFollow()) {
                $seoFieldArray['meta_robots'] = 'index, follow';
            }

            //canonical url
            if (method_exists($entity, 'getCanonicalUrlStatus') && $entity->getCanonicalUrlStatus() && $entity->getCanonicalUrl()) {
                $seoFieldArray['canonical_url'] = $entity->getCanonicalUrl();
            }

            //canonical url for seo tool
            if (method_exists($entity, 'getCanonicalUrl') && $entity->getCanonicalUrl()) {
                $seoFieldArray['canonical_url'] = $entity->getCanonicalUrl();
            }

            // for popular search
            if (method_exists($entity, 'getPupularSearch') && $entity->getPupularSearch()) {
                $seoFieldArray['popular_search'] = $entity->getPupularSearch();
                $seoFieldArray['seo_tool_id'] = $entity->getId();
            }
            if (method_exists($entity, 'getListContentTitle') && $entity->getListContentTitle()) {
                $seoFieldArray['list_content_title'] = $entity->getListContentTitle();
            }
            if (method_exists($entity, 'getListContentDetail') && $entity->getListContentDetail()) {
                $seoFieldArray['list_content_detail'] = $entity->getListContentDetail();
            }
        } elseif (is_array($entity) && count($entity)) {
            //page title
            if (isset($entity['page_title']) && $entity['page_title']) {
                $seoFieldArray['page_title'] = $entity['page_title'];
            } elseif ($defaultEntityValue) {
                $seoFieldArray['page_title'] = $defaultEntityValue;
            }

            //h1 tag
            if (isset($entity['h1_tag']) && $entity['h1_tag']) {
                $seoFieldArray['h1_tag'] = $entity['h1_tag'];
            } elseif ($defaultEntityValue) {
                $seoFieldArray['h1_tag'] = $seoFieldArray['page_title'];
            }

            //meta description
            if (isset($entity['meta_description']) && $entity['meta_description']) {
                $seoFieldArray['meta_description'] = $entity['meta_description'];
            }

            //meta description
            if (isset($entity['meta_keywords']) && $entity['meta_keywords']) {
                $seoFieldArray['meta_keywords'] = $entity['meta_keywords'];
            }

            //image alt
            if (isset($entity['image_alt']) && $entity['image_alt']) {
                $seoFieldArray['image_alt'] = $entity['image_alt'];
            }
            if (isset($entity['image_alt_2']) && $entity['image_alt_2']) {
                $seoFieldArray['image_alt_2'] = $entity['image_alt_2'];
            }
            if (isset($entity['image_alt_3']) && $entity['image_alt_3']) {
                $seoFieldArray['image_alt_3'] = $entity['image_alt_3'];
            }
            if (isset($entity['image_alt_4']) && $entity['image_alt_4']) {
                $seoFieldArray['image_alt_4'] = $entity['image_alt_4'];
            }
            if (isset($entity['image_alt_5']) && $entity['image_alt_5']) {
                $seoFieldArray['image_alt_5'] = $entity['image_alt_5'];
            }
            if (isset($entity['image_alt_6']) && $entity['image_alt_6']) {
                $seoFieldArray['image_alt_6'] = $entity['image_alt_6'];
            }
            if (isset($entity['image_alt_7']) && $entity['image_alt_7']) {
                $seoFieldArray['image_alt_7'] = $entity['image_alt_7'];
            }
            if (isset($entity['image_alt_8']) && $entity['image_alt_8']) {
                $seoFieldArray['image_alt_8'] = $entity['image_alt_8'];
            }

            //meta robots
            if (isset($entity['no_index']) && $entity['no_index'] && isset($entity['no_follow']) && $entity['no_follow']) {
                $seoFieldArray['meta_robots'] = 'noindex, nofollow';
            } elseif ((!isset($entity['no_index']) || !$entity['no_index']) && isset($entity['no_follow']) && $entity['no_follow']) {
                $seoFieldArray['meta_robots'] = 'index, nofollow';
            } elseif (isset($entity['no_index']) && $entity['no_index'] && (!isset($entity['no_follow']) || !$entity['no_follow'])) {
                $seoFieldArray['meta_robots'] = 'noindex, follow';
            } elseif ((!isset($entity['no_index']) || !$entity['no_index']) && (!isset($entity['no_follow']) || !$entity['no_follow'])) {
                $seoFieldArray['meta_robots'] = 'index, follow';
            }

            //canonical url
            if (isset($entity['canonical_url_status']) && $entity['canonical_url_status'] && isset($entity['canonical_url']) && $entity['canonical_url']) {
                $seoFieldArray['canonical_url'] = $entity['canonical_url'];
            }

            //canonical url for seo tool
            if (isset($entity['canonical_url']) && $entity['canonical_url']) {
                $seoFieldArray['canonical_url'] = $entity['canonical_url'];
            }

            // for popular search
            if (isset($entity['popular_search']) && $entity['popular_search'] && isset($entity['seo_tool_id']) && $entity['seo_tool_id']) {
                $seoFieldArray['popular_search'] = $entity['popular_search'];
                $seoFieldArray['seo_tool_id'] = $entity['seo_tool_id'];
            }

            if (isset($entity['list_content_title']) && $entity['list_content_title']) {
                $seoFieldArray['list_content_title'] = $entity['list_content_title'];
            }

            if (isset($entity['list_content_detail']) && $entity['list_content_detail']) {
                $seoFieldArray['list_content_detail'] = $entity['list_content_detail'];
            }
        }
        
        //hard code the seo field
        if (in_array($_SERVER['REQUEST_URI'], self::$SeoHardCodeForUrl)) {
            switch ($_SERVER['REQUEST_URI']) {
                case '/bristol/':
                    $seoFieldArray['page_title'] = 'Trade It in Bristol | Friday-Ad';
                    $seoFieldArray['meta_description'] = ' Buy & Sell your second hand & new items on Friday-Ad (formerly Trade It) for Free! Sell your items, cars, property to others in Bristol';
                    break;
                case '/bristol/motors/cars/':
                    $seoFieldArray['meta_description'] = 'Find Used Cars for Sale in Bristol with Friday-ad (formerly Trade It). Looking to sell your car? We make advertising easy with a few simple steps.';
                    break;
                case '/bristol/for-sale/free-to-collector/':
                    $seoFieldArray['meta_description'] = 'Find free stuff in Bristol with Friday-ad (formerly Trade It). There are thousands of items being give away on Friday-Ad and you can place an ad for free!';
                    break;
                case '/swindon/':
                    $seoFieldArray['meta_description'] = 'Buy & Sell your second hand & new items on Friday-Ad (formerly Trade It) for Free! Sell your items, cars, property to others in Swindon.';
                    break;
                case '/bristol/animals/pets/cats-kittens/':
                    $seoFieldArray['meta_description'] = 'Find Kittens and Cats in Bristol. There are thousands of beautiful kittens and other pets needing a new home on Friday-Ad (formerly Trade It).';
                    break;
                case '/bristol/animals/pets/':
                    $seoFieldArray['meta_description'] = 'Find Pets in Bristol, or find a home for your Pets with the Friday-Ad (formerly Trade It).';
                    break;
                case '/gloucester/':
                    $seoFieldArray['meta_description'] = 'Buy & Sell your second hand & new items on Friday-Ad (formerly Trade It) for Free! Sell your items, cars, property to others in Gloucester.';
                    break;
                case '/bristol/animals/pets/dogs-puppies/':
                    $seoFieldArray['meta_description'] = 'Dogs and Puppies in Bristol. Find your perfect puppy from private sellers and ethical breeders in the Friday-Ad (formerly Trade It) pets section.';
                    break;
                case '/bristol/adult/':
                    $seoFieldArray['meta_description'] = 'Find Adult Services in Bristol. There are thousands of adult services on Friday-Ad (formerly Trade It) and you can place an ad for free!';
                    break;
            }
        }

        return $seoFieldArray;
    }

    /**
     * Return group directory name by id.
     *
     * @param integer $id       Name of directory.
     * @param integer $dirlimit Group directory by no of ids.
     *
     * @return string
     */
    public static function getGroupDirNameById($id, $dirlimit = 100)
    {
        $mod = (ceil($id / $dirlimit) - 1);
        $dirs = ($mod * $dirlimit + 1);
        $dire = ($dirs + $dirlimit - 1);

        return $dirs.'_'.$dire;
    }

    /**
     * Create group directory if not exist.
     *
     * @param string  $parent Parent directory path.
     * @param integer $id     Name of directory.
     * @param integer $dirlimit Group directory by no of ids.
     *
     * @return void
     */
    public static function createGroupDirectory($parent, $id, $dirlimit = 100)
    {
        $tempDir = self::getGroupDirNameById($id, $dirlimit);
        $old     = umask(0);

        if (!is_dir($parent)) {
            mkdir($parent, 0777);
        }

        if (!is_dir($parent.'/'.$tempDir)) {
            mkdir($parent.'/'.$tempDir, 0777);
        }
        umask($old);
    }


    /**
     * This method is used to check whether user is authenticated or not.
     *
     * @param $container Container identifier.
     *
     * @return boolean
     */
    public static function isAuth($container = null)
    {
        return self::getSecurityAuthorizationChecker($container)->isGranted("IS_AUTHENTICATED_REMEMBERED");
    }

    /**
     * This method will check and give secutiry token_storage.
     *
     * @param $container Container identifier.
     *
     * @return \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    public static function getSecurityTokenStorage($container = null)
    {
        if (!$container->has('security.token_storage')) {
            throw new \Exception('Service "security.token_storage" not initialized!');
        }

        return $container->get('security.token_storage');
    }

    /**
     * This method will check and give secutiry authorization_checker.
     *
     * @param $container Container identifier.
     *
     * @return \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     */
    public static function getSecurityAuthorizationChecker($container = null)
    {
        if (!$container->has('security.authorization_checker')) {
            throw new \Exception('Service "security.authorization_checker" not initialized!');
        }

        return $container->get('security.authorization_checker');
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @param object $container Container identifier.
     *
     * @return mixed
     */
    public static function isAdminLoggedIn($container = null)
    {
        if (self::isAuth($container)) {
            $adminRolesArray = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:Role')->getRoleArrayByType('A', $container);
            $user = self::getSecurityTokenStorage($container)->getToken()->getUser();
            $userRole = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserRole($user->getId(), $container);
            if (in_array($userRole, $adminRolesArray)) {
                return $user;
            }
        }

        return false;
    }

    /**
     * This method will convert doctrine object to array.
     *
     * @param object $object Object in which we want to convert the array.
     * @param array  $array  Array that needs to be converted to object.
     *
     * @return object
     */
    public static function convertArrayToDoctrineObject($object, $array)
    {
        foreach ($array as $field => $value) {
            $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if (method_exists($object, $methodName) === true) {
                $object->$methodName($value);
            }
        }

        return $object;
    }

    /**
     * Get user logo.
     *
     * @param object  $container   Container identifier.
     * @param string  $path        Path of user logo.
     * @param integer $userId      User id.
     * @param string  $imageWidth  Image width.
     * @param string  $imageHeight Image height.
     * @param boolean $appendTime  Append time to user image url.
     * @param boolean $isCompany   Company flag.
     * @param integer $userStatus  User status id.
     * @param string  $userName    User profile name.
     *
     * @return string|boolean
     */
    public static function getUserLogo($container, $path, $userId, $imageWidth = "88", $imageHeight = '88', $appendTime = false, $isCompany = false, $userStatus = null, $userName = null)
    {
        if (!$userStatus) {
            $userStatus = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserStatus($userId, $container);
        }

        if (!$userName) {
            $userName = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserProfileName($userId, $container);
        }

        $userName .= ' - Friday-Ad';

        $imagePath = null;

        if ($userId) {
            if (!is_numeric($userId)) {
                if (is_file($container->get('kernel')->getRootDir().'/../web/uploads/tmp/'.$userId.'.jpg')) {
                    if ($isCompany) {
                        return '<img src="'.$container->getParameter('fa.static.shared.url').'/uploads/tmp/'.$userId.'.jpg'.($appendTime ? '?'.time() : null).'" alt="'.$userName.'"  />';
                    } else {
                        return '<span style="background-image: url('.$container->getParameter('fa.static.shared.url').'/uploads/tmp/'.$userId.'.jpg'.($appendTime ? '?'.time() : null).')" title="'.$userName.'" />';
                    }
                } else {
                    $noImageName = 'user-icon.svg';
                    if ($isCompany) {
                        $noImageName = 'user-no-logo.svg';
                    }

                    if (!$imageWidth && !$imageHeight) {
                        return ($isCompany ? '<div class="profile-placeholder">' : '').'<img src="'.$container->getParameter('fa.static.url').'/fafrontend/images/'.$noImageName.'" alt="'.$userName.'" '.(!$isCompany ? 'class="pvt-no-img"':null).' />'.($isCompany ? '</div>' : '');
                    } else {
                        return ($isCompany ? '<div class="profile-placeholder">' : '').'<img src="'.$container->getParameter('fa.static.url').'/fafrontend/images/'.$noImageName.'" width="'.$imageWidth.'" height="'.$imageHeight.'" alt="'.$userName.'" '.(!$isCompany ? 'class="pvt-no-img"':null).' />'.($isCompany ? '</div>' : '');
                    }
                }
            } else {
                $imagePath  = $container->get('kernel')->getRootDir().'/../web/'.$path.'/'.$userId.'.jpg';
            }
        }

        if (is_file($imagePath)) {
            if (($imageWidth==null && $imageHeight== null) || ($imageWidth =='' && $imageHeight=='') || ($imageWidth ==0 || $imageHeight==0)) {
                if ($isCompany) {
                    return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).'<img src="'.$container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.'.jpg'.($appendTime ? '?'.time() : null).'" alt="'.$userName.'" />';
                } else {
                    return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).'<span style="background-image: url('.$container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.'.jpg'.($appendTime ? '?'.time() : null).')" title="'.$userName.'" />';
                }
            } else {                
                if (!file_exists($container->get('kernel')->getRootDir().'/../web/'.$path.'/'.$userId.'_'.$imageWidth.'X'.$imageHeight.'.jpg')) {
                    if ($isCompany) {
                        $orgImagPath = $container->getParameter('fa.company.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
                    } else {
                        $orgImagPath = $container->getParameter('fa.user.image.dir').'/'.CommonManager::getGroupDirNameById($userId, 5000);
                    }
                    exec('convert '.$orgImagPath.DIRECTORY_SEPARATOR.$userId.'.jpg -resize '.$imageWidth.'X'.$imageHeight.' -background white -gravity center -extent '.$imageWidth.'X'.$imageHeight.' '.$orgImagPath.DIRECTORY_SEPARATOR.$userId.'_'.$imageWidth.'X'.$imageHeight.'.jpg');
                }

                $newImagePath = $container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.'_'.$imageWidth.'X'.$imageHeight.'.jpg'.($appendTime ? '?'.time() : null);

                if ($isCompany) {
                    return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).'<img src="'.$newImagePath.'" width="'.$imageWidth.'" height="'.$imageHeight.'" alt="'.$userName.'" />';
                } else {
                    return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).'<span style="background-image: url('.$newImagePath.')" title="'.$userName.'"></span>';
                }
            }
        } else {
            $noImageName = 'user-icon.svg';
            if ($isCompany) {
                $noImageName = 'user-no-logo.svg';
            }
            if (!$imageWidth && !$imageHeight) {
                return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).($isCompany ? '<div class="profile-placeholder">' : '').'<img src="'.$container->getParameter('fa.static.url').'/fafrontend/images/'.$noImageName.'" alt="'.$userName.'" '.(!$isCompany ? 'class="pvt-no-img"':null).' />'.($isCompany ? '</div>' : '');
            } else {
                return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).($isCompany ? '<div class="profile-placeholder">' : '').'<img src="'.$container->getParameter('fa.static.url').'/fafrontend/images/'.$noImageName.'" width="'.$imageWidth.'" height="'.$imageHeight.'" alt="'.$userName.'" '.(!$isCompany ? 'class="pvt-no-img"':null).' />'.($isCompany ? '</div>' : '');
            }
        }
    }

    /**
     * Get user logo.
     *
     * @param object  $container   Container identifier.
     * @param integer $userId      User id.
     * @param boolean $appendTime  Append time in url.
     * @param boolean $getUrlOnly  Get url only.
     * @param string  $userName    User profile name.
     *
     * @return string|boolean
     */
    public static function getUserLogoByUserId($container, $userId, $appendTime = false, $getUrlOnly = false, $userName = null)
    {
        if (!is_numeric($userId)) {
            $imagePath  = $container->get('kernel')->getRootDir().'/../web/uploads/tmp/'.$userId.'_org.jpg';
            if (is_file($imagePath)) {
                if ($getUrlOnly) {
                    return $container->getParameter('fa.static.shared.url').'/uploads/tmp/'.$userId.'_org.jpg'.($appendTime ? '?'.time() : null);
                }
            }
        }

        $userStatus = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserStatus($userId, $container);
        $userRole   = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserRole($userId, $container);
        if (!$userName) {
            $userName = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserProfileName($userId, $container);
        }

        $userName .= ' - Friday-Ad';
        $imagePath = null;

        if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
            $path = $container->getParameter('fa.company.image.dir').'/'.self::getGroupDirNameById($userId, 5000);
            $imagePath  = $container->get('kernel')->getRootDir().'/../web/'.$path.'/'.$userId.'.jpg';
        } elseif ($userRole == RoleRepository::ROLE_SELLER) {
            $path = $container->getParameter('fa.user.image.dir').'/'.self::getGroupDirNameById($userId, 5000);
            $imagePath  = $container->get('kernel')->getRootDir().'/../web/'.'/'.$path.'/'.$userId.'.jpg';
        }

        if (is_file($imagePath)) {
            if ($getUrlOnly) {
                return $container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.'.jpg'.($appendTime ? '?'.time() : null);
            } else {
                if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                    return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).'<img src="'.$container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.'.jpg'.($appendTime ? '?'.time() : null).'" alt="'.$userName.'" />';
                } else {
                    return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).'<span style="background-image: url('.$container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.'.jpg'.($appendTime ? '?'.time() : null).')" title="'.$userName.'"></span>';
                }
            }
        } else {
            if ($getUrlOnly) {
                return null;
            } else {
                $noImageName = 'user-icon.svg';
                if ($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) {
                    $noImageName = 'user-no-logo.svg';
                }

                return ($userStatus == EntityRepository::USER_STATUS_INACTIVE_ID ? '<span class="inactive-profile">Inactive</span>': null).(($userRole == RoleRepository::ROLE_BUSINESS_SELLER || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) ? '<div class="profile-placeholder">' : '').'<img src="'.$container->getParameter('fa.static.url').'/fafrontend/images/'.$noImageName.($appendTime ? '?'.time() : null).'" alt="'.$userName.'" '.($userRole == RoleRepository::ROLE_SELLER? 'class="pvt-no-img"':null).' />'.(($userRole == RoleRepository::ROLE_BUSINESS_SELLER  || $userRole == RoleRepository::ROLE_NETSUITE_SUBSCRIPTION) ? '</div>' : '');
            }
        }
    }

    /**
     * This method will give time by adding duration into it.
     *
     * @param string  $duration  Time duration, duration format should be 1d 1m 1w.
     * @param integer $time      Time in integer.
     * @param string  $plusMinus Add or substract.
     *
     * @return time
     */
    public static function getTimeFromDuration($duration, $time = null, $plusMinus = '+')
    {
        if (!$time) {
            $time = time();
        }

        $durationArray = explode(' ', $duration);

        foreach ($durationArray as $durationValue) {
            $digit = intval($durationValue);
            if (strpos($durationValue, 'm') !== false) {
                $time = strtotime($plusMinus.$digit.' month', $time);
            } elseif (strpos($durationValue, 'w') !== false) {
                $time = strtotime($plusMinus.$digit.' week', $time);
            } elseif (strpos($durationValue, 'd') !== false) {
                $time = strtotime($plusMinus.$digit.' day', $time);
            } elseif (strpos($durationValue, 'min') !== false) {
                $time = strtotime($plusMinus.$digit.' minutes', $time);
            }
        }

        return $time;
    }

    /**
     * Sends error email.
     *
     * @param object $container        Container identifier.
     * @param string $subject          Email subject.
     * @param string $exceptionMessage Exception message.
     * @param string $stackTrace       Exception stack trace.
     *
     * @return boolean
     */
    public static function sendErrorMail($container, $subject, $exceptionMessage, $stackTrace)
    {
        $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setSender($container->getParameter('mailer_sender_email'))
        ->setTo($container->getParameter('fa.error.email'))
        ->setBody(
            $container->get('templating')->render(
                'FaCoreBundle:Exception:mailError.html.twig',
                array(
                    'exceptionMessage' => $exceptionMessage,
                    'stackTrace' => $stackTrace,
                )
            ),
            'text/html'
        );
        return $container->get('mailer')->send($message);
    }

    /**
     * Get month choices.
     *
     * @return array
     */
    public static function getMonthChoices()
    {
        $monthArray = array();

        $monthArray['1'] = 'January';
        $monthArray['2'] = 'February';
        $monthArray['3'] = 'March';
        $monthArray['4'] = 'April';
        $monthArray['5'] = 'May';
        $monthArray['6'] = 'June';
        $monthArray['7'] = 'July';
        $monthArray['8'] = 'August';
        $monthArray['9'] = 'September';
        $monthArray['10'] = 'October';
        $monthArray['11'] = 'November';
        $monthArray['12'] = 'December';

        return $monthArray;
    }

    /**
     * Get month name.
     *
     * @return string
     */
    public static function getMonthName($month)
    {
        $monthArray = self::getMonthChoices();

        return (isset($monthArray[$month]) ? $monthArray[$month] :null);
    }

    /**
     * Get class or template or config variable name by category id.
     *
     * @param integer $rootCategoryId Root category id.
     * @param boolean $classNameFlag  Flag for class name.
     *
     * @return mixed
     */
    public static function getCategoryClassNameById($rootCategoryId, $classNameFlag = false)
    {
        $className = null;
        switch ($rootCategoryId) {
            case CategoryRepository::FOR_SALE_ID:
                $className = 'for_sale';
                break;
            case CategoryRepository::MOTORS_ID:
                $className = 'motors';
                break;
            case CategoryRepository::JOBS_ID:
                $className = 'jobs';
                break;
            case CategoryRepository::SERVICES_ID:
                $className = 'services';
                break;
            case CategoryRepository::PROPERTY_ID:
                $className = 'property';
                break;
            case CategoryRepository::ANIMALS_ID:
                $className = 'animals';
                break;
            case CategoryRepository::COMMUNITY_ID:
                $className = 'community';
                break;
            case CategoryRepository::ADULT_ID:
                $className = 'adult';
                break;
        }

        if ($classNameFlag) {
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));
        }

        return $className;
    }

    /**
     * Get common sorting array.
     *
     * @param object  $container      Container identifier.
     * @param integer $rootCategoryId Root category id.
     * @param array   $searchParams   Search param array.
     *
     * @return array
     */
    public static function getSortingArray($container, $rootCategoryId = null, $searchParams = array())
    {
        $sortingArray      = array();
        $translator        = self::getTranslator($container);
        $parentCategoryIds = array();

        $sortingArray['item__weekly_refresh_published_at|desc'] = $translator->trans('Most recent');

        if (isset($searchParams['item__category_id']) && $searchParams['item__category_id']) {
            $parentCategoryIds = array_keys($container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($searchParams['item__category_id'], false, $container));
        }

        // if root category is community then check for what's on category.
        if ($rootCategoryId == CategoryRepository::COMMUNITY_ID && isset($parentCategoryIds[1]) && $parentCategoryIds[1] == CategoryRepository::WHATS_ON_ID) {
            unset($sortingArray['item__weekly_refresh_published_at|desc']);
            $sortingArray['ad_community__event_end|asc'] = $translator->trans('Event date');
        }

        // do not add price sorting for specific category.
        if (!$rootCategoryId || ($rootCategoryId && !in_array($rootCategoryId, array(CategoryRepository::JOBS_ID, CategoryRepository::COMMUNITY_ID, CategoryRepository::SERVICES_ID, CategoryRepository::ADULT_ID)))) {
            $sortingArray['item__price|asc']  = $translator->trans('Price: lowest first');
            $sortingArray['item__price|desc'] = $translator->trans('Price: highest first');
        }

        //add distance sorting if location or distance is available
        if (isset($searchParams['item__location']) && $searchParams['item__location'] != LocationRepository::COUNTY_ID && (!isset($searchParams['item__distance']) || (isset($searchParams['item__distance']) && $searchParams['item__distance'] >= 0 && $searchParams['item__distance'] <= 200))) {
            $sortingArray['item__geodist|asc'] = $translator->trans('Nearest first');
        }

        //get category wise sorting parameters
        if ($rootCategoryId) {
            $className  = self::getCategoryClassNameById($rootCategoryId, true);
            $repository = $container->get('doctrine')->getManager()->getRepository('FaAdBundle:Ad'.$className);
            if (method_exists($repository, 'getSortingArray')) {
                $sortingArray = $sortingArray + $repository->getSortingArray($container);
            }
        }

        return $sortingArray;
    }

    /**
     * Trim passed text to specified length.
     *
     * @param string  $text      Passed text.
     * @param integer $length    Trim upto this length default (10).
     * @param string  $sign      Continue sign default(...).
     * @param boolean $stripHtml Strip html flag.
     *
     * @return void
     */
    public static function trimText($text, $length = 10, $sign = '...', $stripHtml = true)
    {
        if ($stripHtml) {
            $text = strip_tags($text);
        }

        if (mb_strlen($text, 'UTF-8') > $length) {
            return mb_substr($text, 0, $length, 'UTF-8').$sign;
        }

        return $text;
    }

    /**
     * Trim text by words.
     *
     * @param string  $text          String to truncate.
     * @param integer $limit         Limit of words.
     * @param string  $postFixString Postfix string if length is more than limit.
     *
     * @return string
     */
    public static function trimTextByWords($text, $limit, $postFixString = '...')
    {
        if (str_word_count($text, 0) > $limit) {
            $words = str_word_count($text, 2);
            $pos   = array_keys($words);
            $text  = substr($text, 0, $pos[$limit]).$postFixString;
        }

        return $text;
    }

    /**
     * Get distance between two lat long.
     *
     * @param float $lat1 First latitude.
     * @param float $lon1 First longitude.
     * @param float $lat2 Second latitude.
     * @param float $lon2 Second longitude.
     * @param float $unit Unit of distance.
     *
     * @return number
     */
    public static function getDistance($lat1, $lon1, $lat2, $lon2, $unit = 'M')
    {
        $theta = $lon1 - $lon2;
        $dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit  = strtoupper($unit);

        if ($unit == "K") {
            return round($miles * 1.609344);
        } elseif ($unit == "N") {
            return round($miles * 0.8684);
        } else {
            return round($miles);
        }
    }

    /**
     * Get current location text from cookie.
     *
     * @param string $cookieLocation
     */
    public static function getCurrentLocationText($cookieLocation)
    {
        $locationText = 'uk';
        // fetch from cookie if user has no location
        if ($cookieLocation && $cookieLocation != CommonManager::COOKIE_DELETED) {
            $cookieLocation = get_object_vars(json_decode($cookieLocation));
            if (isset($cookieLocation['postcode']) && $cookieLocation['postcode']) {
                $locationText = $cookieLocation['postcode'];
            } elseif (isset($cookieLocation['town']) && $cookieLocation['town']) {
                $locationText = $cookieLocation['town'];
            } elseif (isset($cookieLocation['county']) && $cookieLocation['county']) {
                $locationText = $cookieLocation['county'];
            }
        }

        return $locationText;
    }

    /**
     *
     * @param object   $container   Container identifier.
     * @param interger $adId        Ad id.
     * @param string   $imagePath   Ad image path.
     * @param string   $imageHash   Ad image hash.
     * @param string   $size        Ad image size.
     * @param string   $image_name  Image Name
     *
     * @return string
     */
    public static function getAdImageUrl($container, $adId, $imagePath, $imageHash, $size = null, $processed = 0, $image_name = null)
    {
        if ($processed == 1) {
            if ($image_name != '') {
                $imageUrl = $container->getParameter('fa.static.aws.url').'/'.$imagePath.'/'.$image_name.($size ? '_'.$size : '').'.jpg?'.$imageHash;
            } else {
                $imageUrl = $container->getParameter('fa.static.aws.url').'/'.$imagePath.'/'.$adId.'_'.$imageHash.($size ? '_'.$size : '').'.jpg';
            }
        } else {
            $imageUrl = $container->getParameter('fa.static.shared.url').'/'.$imagePath.'/'.$adId.'_'.$imageHash.($size ? '_'.$size : '').'.jpg';
        }
        return $imageUrl;
    }

    /**
     * Get image alt string.
     *
     * @param object   $container      Container identifier.
     * @param string   $imageAltString Ad image alt string.
     * @param object   $adSolrObj      Ad solr object.
     *
     * @return string
     */
    public static function getAdImageAlt($container, $imageAltString, $adSolrObj)
    {
        $seoManager = $container->get('fa.seo.manager');
        $parsedString = $seoManager->parseAdDetailSeoString($imageAltString, $adSolrObj);

        return trim($parsedString);
    }

    /**
     * Get user site image.
     *
     * @param object   $container  Container identifier.
     * @param interger $userSiteId User site id.
     * @param string   $imagePath  Ad image path.
     * @param string   $imageHash  Ad image hash.
     * @param string   $size       Ad image size.
     *
     * @return string
     */
    public static function getUserSiteImageUrl($container, $userSiteId, $imagePath, $imageHash, $size = null)
    {
        $imageUrl = $container->getParameter('fa.static.shared.url').'/'.$imagePath.'/'.$userSiteId.'_'.$imageHash.($size ? '_'.$size : '').'.jpg';

        return $imageUrl;
    }

    /**
     * Format price with currency.
     *
     * @param integer/float $price  Object or array.
     * @param string        $locale Locale.
     *
     * @return string
     */
    public static function formatCurrency($price, $container, $locale = null)
    {
        if (!$locale) {
            $locale = $container->getParameter('locale');
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        //$formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
        $formattedPrice = $formatter->format($price, \NumberFormatter::TYPE_DOUBLE);

        $formattedPrice = preg_replace('/(\.00)/', '', $formattedPrice);

        return $formattedPrice;
    }

    /**
     * Returns whether to show in search or not.
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container interface.
     *
     * @return boolean
     */
    public static function showPriceInSearchFilter($categoryId, $container)
    {
        $showFlag     = true;
        $em           = $container->get('doctrine')->getManager();
        $categoryPath = array_keys($em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container));

        if (isset($categoryPath[0]) && in_array($categoryPath[0], array(CategoryRepository::JOBS_ID, CategoryRepository::COMMUNITY_ID, CategoryRepository::SERVICES_ID, CategoryRepository::ADULT_ID))) {
            $showFlag = false;
        }

        return $showFlag;
    }

    /**
     * Format date.
     *
     * @param integer $date   Date in integer.
     * @param string  $locale Locale.
     *
     * @return string
     */
    public static function formatDate($date, $container, $dateType = \IntlDateFormatter::SHORT, $timeType = \IntlDateFormatter::NONE, $pattern = null, $locale = null)
    {
        if (!$locale) {
            $locale = $container->getParameter('locale');
        }

        $formatter = new \IntlDateFormatter($locale, $dateType, $timeType, null, null, $pattern);

        return $formatter->format($date);
    }

    /**
     * Recursive array search.
     *
     * @param mixed $needle
     * @param array $haystack
     *
     * @return mixed
     */
    public static function recursiveArraySearch($needle, $haystack)
    {
        foreach ($haystack as $key => $value) {
            $current_key=$key;
            if ($needle === $value || (is_array($value) && self::recursiveArraySearch($needle, $value) !== false)) {
                return $current_key;
            }
        }

        return false;
    }

    /**
     * Get year range.
     *
     * @return array
     */
    public static function getRegYearChoices()
    {
        $years     = array('pre-1970' => 'Pre 1970');
        $yearRange = range(1971, date("Y"));
        $yearRange = array_combine($yearRange, $yearRange);

        $years = $years + $yearRange;
        return array_reverse($years, true);
    }

    /**
     * Get year range.
     *
     * @return array
     */
    public static function getYearChoices()
    {
        $years     = array('pre-1970' => 'Pre 1970');
        $yearRange = range(1971, (date("Y") + 4));
        $yearRange = array_combine($yearRange, $yearRange);

        $years = $years + $yearRange;
        return array_reverse($years, true);
    }

    /**
     * Get mileage range.
     *
     * @return array
     */
    public static function getMileageChoices()
    {
        $mileage = array(
                       '0-25000'      => '0 - 25000',
                       '25001-50000'  => '25000 - 50000',
                       '50001-75000'  => '50000 - 75000',
                       '75001-100000' => '75000 - 100000',
                       '100000+'      => '100000+'
                   );

        return $mileage;
    }

    /**
     * Get mileage range by value.
     *
     * @return boolean
     */
    public static function getMileageRangeByValue($value)
    {
        foreach (self::getMileageChoices() as $mileageRange => $mileageText) {
            $range = ($mileageRange == '100000+' ? '100000+-' : $mileageRange);

            list($min, $max) = explode('-', $range);
            $min = str_replace('+', '', $min);

            if (($max && $value >= $min && $value <= $max) || (!$max && $value > $min)) {
                return $mileageRange;
            }
        }

        return null;
    }

    /**
     * Get yes no valuesbased on passed value.
     *
     * @param integer $value Value.
     *
     * @return string
     */
    public static function getYesNoValue($value)
    {
        $values[1] = 'Yes';
        $values[0] = 'No';

        return (isset($values[$value]) ? $values[$value] : null);
    }

    /**
     * Get time period choices for given vertical.
     *
     * @param string $vertical  Vertical.
     * @param Object $container Container identifier.
     *
     * @return string
     */
    public static function getTimePeriodChoices($vertical, $container = null)
    {
        $translator = CommonManager::getTranslator($container);
        $choices = array(
                       ''               => $translator->trans('Any date'),
                       'today'          => $translator->trans('Today'),
                       'tomorrow'       => $translator->trans('Tomorrow'),
                       'week'           => $translator->trans('Within a week'),
                       'month'          => $translator->trans('Within a month'),
                       'specific-dates' => $translator->trans('Specific dates')
                   );

        if ($vertical == 'property') {
            unset($choices['today'], $choices['tomorrow']);
        }

        return $choices;
    }

    /**
     * Download file.
     *
     * @param string $fileNameWithPath     file name with full path.
     * @param string $fileNameWhenDownload file name which we want to give when dowload.
     * @param string $forceDownload        download must not an option for open file if set to true.
     *
     * @return void
     */
    public static function downloadFile($fileNameWithPath, $fileNameWhenDownload, $forceDownload = false)
    {
        if (file_exists($fileNameWithPath)) {
            header('Content-Encoding: UTF-8');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="'.basename($fileNameWhenDownload).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileNameWithPath));
            header('Content-Type: application/octet-stream');

            if ($forceDownload) {
                header('Content-Type: application/octet-stream');
            }

            //echo "\xEF\xBB\xBF";
            return readfile($fileNameWithPath);
        }
    }

    /**
     * Add http scheme if its not there.
     *
     * @param string $url Url string.
     *
     * @return string
     */
    public static function addHttpToUrl($url)
    {
        if (!preg_match("~^(?:ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        return $url;
    }

    /**
     *
     * @param object   $container Container identifier.
     * @param interger $userId    User id.
     * @param string   $path      Company logo path.
     * @param string   $size      Company logo size.
     *
     * @return string
     */
    public static function getUserCompanyLogoUrl($container, $userId, $path, $size = null)
    {
        return $container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.($size ? '_'.$size : '').'.jpg';
    }

    /**
     *
     * @param object   $container Container identifier.
     * @param interger $userId    User id.
     * @param string   $path      User image path.
     * @param string   $size      User image size.
     *
     * @return string
     */
    public static function getUserImageUrl($container, $userId, $path, $size = null)
    {
        return $container->getParameter('fa.static.shared.url').'/'.$path.'/'.$userId.($size ? '_'.$size : '').'.jpg';
    }

    /**
     * Get full duration from short form
     *
     * @param string $shortDuration Short form duration string.
     *
     * @return mixed
     */
    public static function getFullDurationFromShortForm($shortDuration)
    {
        $fullDuration = null;
        $term = substr($shortDuration, -1);
        $term = strtolower($term);
        $len  = strlen($shortDuration);
        switch ($term) {
            case 'd':
                $fullDuration = substr($shortDuration, 0, $len-1).' day';
                break;
            case 'w':
                $fullDuration = substr($shortDuration, 0, $len-1).' week';
                break;
            case 'm':
                $fullDuration = substr($shortDuration, 0, $len-1).' month';
                break;
        }

        return $fullDuration;
    }

    /**
     * Remove admin back url
     *
     * @param object $container Container identifier.
     */
    public static function removeAdminBackUrl($container)
    {
        $session = $container->get('session');
        if ($session->has('admin_back_url')) {
            $session->remove('admin_back_url');
        }

        if ($session->has('admin_cancel_url')) {
            $session->remove('admin_cancel_url');
        }
    }

    /**
     * Set admin back url
     *
     * @param object $request   A Request object.
     * @param object $container Container identifier.
     */
    public static function setAdminBackUrl($request, $container)
    {
        $referer    = $request->headers->get('referer');
        $currentUrl = $request->getUri();
        $cancelUrl  = $request->headers->get('referer');

        $session = $container->get('session');
        if ($referer && (strpos($referer, '/header_image') || !strpos($referer, '/new')) && !strpos($referer, '/preview') && !strpos($referer, '/show')) {
            $session->set('admin_back_url', $currentUrl);
            if ($referer && !strpos($referer, '/edit')) {
                $session->set('admin_cancel_url', $cancelUrl);
            }
        }
    }

    /**
     * Get admin back url
     *
     * @param object $container Container identifier.
     *
     * @return mixed
     */
    public static function getAdminBackUrl($container)
    {
        $session = $container->get('session');
        if ($session->has('admin_back_url')) {
            return $session->get('admin_back_url');
        } else {
            return null;
        }
    }

    /**
     * Get admin cancel url
     *
     * @param object $container Container identifier.
     *
     * @return mixed
     */
    public static function getAdminCancelUrl($container)
    {
        $session = $container->get('session');
        if ($session->has('admin_cancel_url')) {
            return $session->get('admin_cancel_url');
        } else {
            return null;
        }
    }

    /**
     * Get banner page routes.
     *
     * @return array
     */
    public static function getBannerPageRoutes()
    {
        $routesArray = array(
                        BannerPageRepository::PAGE_HOME           => 'fa_frontend_homepage',
                        BannerPageRepository::PAGE_SEARCH_RESULTS => 'listing_page',
                        BannerPageRepository::PAGE_AD_DETAILS     => 'ad_detail_page',
                        BannerPageRepository::PAGE_LANDING_PAGE   => 'landing_page_category',
                        BannerPageRepository::PAGE_LANDING_PAGE.'l'   => 'landing_page_category_location',
                       );

        return $routesArray;
    }

    /**
     * This method will check and give logged in admin user.
     *
     * @return userobj
     */
    public static function getLoggedInUser($container)
    {
        if (self::isAuth($container)) {
            return self::getSecurityTokenStorage($container)->getToken()->getUser();
        }

        return false;
    }

    /**
     * Get user report fields choices.
     *
     * @return array
     */
    public static function getUserReportFieldsChoices()
    {
        $fieldsArray = array_merge(self::getUserReportBasicFieldsArray(), self::getUserReportLocationFieldsArray(), self::getUserReportDailyBasicFieldsArray(), self::getUserReportCategoryFieldsArray(), self::getUserReportEditionFieldsArray(), self::getUserReportDateFieldsArray(), self::getUserReportBooleanFieldsArray(), self::getUserReportProfilePackageFieldsArray());
        asort($fieldsArray);

        return $fieldsArray;
    }

    /**
     * In array multi key search version.
     *
     * @param array  $arrayToSearch key array.
     * @param array  $arrayInSearch key array.
     * @param string $option        all or any string.
     *
     * @return boolean
     */
    public static function inArrayMulti($arrayToSearch, $arrayInSearch, $option = 'any')
    {
        $searchCount = 0;
        foreach ($arrayToSearch as $key => $value) {
            if (in_array($value, $arrayInSearch)) {
                $searchCount++;
            }
        }

        if ($searchCount > 0) {
            if ($option == 'all' && $searchCount == count($arrayToSearch)) {
                return true;
            } elseif ($option == 'any') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user report basic fields array.
     *
     * @return array
     */
    public static function getUserReportBasicFieldsArray()
    {
        return array('username' => 'Email address',  'is_new' => 'New', 'total_ad' => 'Total ads', 'total_active_ad' => 'Total active ads', 'phone' => 'User phone');
    }

    /**
     * Get user report basic fields array.
     *
     * @return array
     */
    public static function getUserReportDailyBasicFieldsArray()
    {
        $fieldsArray = array();

        $fieldsArray['renewed_ads'] = 'Renewed ads';
        $fieldsArray['expired_ads'] = 'Expired ads';
        $fieldsArray['cancelled_ads'] = 'Cancelled ads';
        $fieldsArray['number_of_ad_placed'] = 'Number of ads placed';
        $fieldsArray['number_of_ad_sold'] = 'Number of ads sold';
        $fieldsArray['number_of_ads_to_renew'] = 'Number of ads to renew';
        $fieldsArray['number_of_free_ads'] = 'Number of free ads';
        $fieldsArray['number_of_paid_ads'] = 'Number of paid ads';
        $fieldsArray['saved_searches'] = 'Saved searches';
        $fieldsArray['total_spent'] = 'Total spent';
        $fieldsArray['items_in_shopping_basket'] = 'Items in shopping basket';
        $fieldsArray['abandoned_items_in_shopping_basket'] = 'Abandoned items in shopping basket';
        $fieldsArray['failed_payments'] = 'Failed payments';
        $fieldsArray['rating_scales'] = 'Rating scales';
        $fieldsArray['profile_page_view_count'] = 'Profile views';
        $fieldsArray['profile_page_email_sent_count'] = 'Emails sent';
        $fieldsArray['profile_page_website_url_click_count'] = 'Website click';
        $fieldsArray['profile_page_phone_click_count'] = 'Phone clicks';
        $fieldsArray['profile_page_social_links_click_count'] = 'Social clicks';
        $fieldsArray['profile_page_map_click_count'] = 'Map views';

        return $fieldsArray;
    }

    /**
     * Get user report boolean fields array.
     *
     * @return array
     */
    public static function getUserReportBooleanFieldsArray()
    {
        return array('is_active' => 'Is Active', 'is_facebook_verified' => 'Is facebook verified', 'is_email_verified' => 'Is email verified', 'is_paypal_vefiried' => 'Is paypal verified');
    }

    /**
     * Get user report category fields array.
     *
     * @return array
     */
    public static function getUserReportCategoryFieldsArray()
    {
        return array('category' => 'Category', 'class' => 'Class', 'subclass' => 'Sub class', 'sub_sub_class' => 'Sub sub class');
    }

    /**
     * Get user report category fields array.
     *
     * @return array
     */
    public static function getUserReportDateFieldsArray()
    {
        return array('signup_date' => 'Signup date', 'first_paa' => 'First ad placed date', 'last_paa' => 'Most recent ad placed date');
    }

    /**
     * Get user report category fields array.
     *
     * @return array
     */
    public static function getUserReportEditionFieldsArray()
    {
        return array('edition' => 'Print edition');
    }

    /**
     * Get user report package fields array.
     *
     * @return array
     */
    public static function getUserReportProfilePackageFieldsArray()
    {
        return array('package_name' => 'Profile package', 'package_revenue' => 'Profile package revenue', 'package_cancelled' => 'Profile package cancelled');
    }

    /**
     *
     * @param object $container Container identifier.
     * @param string $path      Image path.
     * @param string $imageName Name of image.
     *
     * @return string
     */
    public static function getSharedImageUrl($container, $path, $imageName)
    {
        return $container->getParameter('fa.static.shared.url').'/'.$path.'/'.$imageName;
    }

    /**
     *
     * @param object $container Container identifier.
     * @param string $path      Image path.
     * @param string $imageName Name of image.
     *
     * @return string
     */
    public static function getStaticImageUrl($container, $path, $imageName)
    {
        return $container->getParameter('fa.static.url').'/'.$path.'/'.$imageName;
    }

    /**
     * Get ad status class name by status id.
     *
     * @param integer $statusId status id.
     *
     * @return string
     */
    public static function getAdStatusCssClassByStatusId($statusId)
    {
        $choices = array(
            EntityRepository::AD_STATUS_LIVE_ID          => 'live-status',
            EntityRepository::AD_STATUS_DRAFT_ID         => 'draft-status',
            EntityRepository::AD_STATUS_IN_MODERATION_ID => 'moderation-status',
            EntityRepository::AD_STATUS_REJECTED_ID      => 'rejected-status',
            EntityRepository::AD_STATUS_REJECTEDWITHREASON_ID => 'rejected-status',
            EntityRepository::AD_STATUS_EXPIRED_ID => 'rejected-status',
        );

        if (isset($choices[$statusId])) {
            return $choices[$statusId];
        } else {
            return $choices[EntityRepository::AD_STATUS_LIVE_ID];
        }
    }

    /**
     * Get star rating labels.
     *
     * @param object $container Container identifier.
     *
     * @return array
     */
    public static function getStraRatingLabels($container)
    {
        $translator = self::getTranslator($container);
        $rating     = array(
            '1' => $translator->trans('Terrible!'),
            '2' => $translator->trans('Poor!'),
            '3' => $translator->trans('Average!'),
            '4' => $translator->trans('Very good!'),
            '5' => $translator->trans('Excellent!'),
        );

        return $rating;
    }

    /**
     * Get youtube video id.
     *
     * @param string $url Video url
     *
     * @return string
     */
    public static function getYouTubeVideoId($url)
    {
        $videoId = null;

        if ($url) {
            if (preg_match('%(?:youtube(?:-nocookie)?.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu.be/)([^"&?/ ]{11})%i', $url, $match)) {
                $videoId = $match[1];
            }
        }

        return $videoId;
    }

    /**
     * get youtube video image url
     *
     * @param string $videoId Video id
     *
     * @return string
     */
    public static function getYouTubeViewImageUrl($videoId)
    {
        $viedoImgUrl = null;

        if ($videoId) {
            $viedoImgUrl = 'https://img.youtube.com/vi/'.$videoId.'/0.jpg';
        }

        return $viedoImgUrl;
    }

    /**
     * Get transcation js.
     *
     * @param string $trans
     *
     * @return string
     */
    public static function getGaTranscationJs($trans)
    {
        $transactionjs = '';

        if (isset($trans['ID'])) {
            $transactionjs .= <<<HTML
    ga('ecommerce:addTransaction', {
      'id': '{$trans['ID']}',
      'affiliation': '{$trans['Affiliation']}',
      'revenue': '{$trans['Revenue']}',
      'shipping': '{$trans['Shipping']}',
      'tax': '{$trans['Tax']}',
      'currency': '{$trans['Currency']}'
    });
HTML;
        }
        return $transactionjs;
    }

    /**
     * Get item js.
     *
     * @param string $trans
     *
     * @return string
     */
    public static function getGaItemJs($trans)
    {
        $itemjs = '';

        if (isset($trans['items'])) {
            foreach ($trans['items'] as $item) {
                $itemjs .=  <<<HTML
    ga('ecommerce:addItem',{
  'id': '{$trans['ID']}',
  'name': '{$item['Name']}',
  'sku': '{$item['SKU']}',
  'category': "{$item['Category']}",
  'price': '{$item['Price']}',
  'quantity': '{$item['Quantity']}'
});
HTML;
            }
        }
        return $itemjs;
    }

    /**
     * Generate guid.
     *
     * @param integer $userId
     * @param string  $email
     *
     * @return string.
     */
    public static function generateGuid($email)
    {
        return md5('@#$'.strtolower($email).'$#@'.'GUID');
    }

    /**
     * This function is used to get repository.
     *
     * @return Object
     */
    public static function getHistoryRepository($container, $repository)
    {
        return $container->get('doctrine')->getManager('history')->getRepository($repository);
    }

    /**
     * This function is used to get repository.
     *
     * @return Object
     */
    public static function getTiHistoryRepository($container, $repository)
    {
        return $container->get('doctrine')->getManager('ti_history')->getRepository($repository);
    }

    /**
     * This function is used to get repository.
     *
     * @return Object
     */
    public static function getEntityRepository($container, $repository)
    {
        return $container->get('doctrine')->getManager()->getRepository($repository);
    }

    /**
     * Get all files in given path
     *
     * @param string $path       Directory path to read.
     * @param array  $extentions List of extentions to show from given path.
     *
     * @return array
     */
    public static function listDirFileByDate($path, $extentions)
    {
        $dir = opendir($path);
        $list = array();
        while ($file = readdir($dir)) {
            $ext = pathinfo($path.$file, PATHINFO_EXTENSION);
            if (in_array($ext, $extentions)) {
                $ctime = filectime($path . $file) . ',' . $file;
                $list[$ctime] = $file;
            }
        }
        closedir($dir);
        krsort($list);

        return $list;
    }

    /**
     * Get user report basic fields array.
     *
     * @return array
     */
    public static function getUserReportLocationFieldsArray()
    {
        return array('postcode' => 'Postcode', 'town' => 'Town');
    }

    /**
     * Sort a 2 dimensional array based on 1 or more indexes.
     *
     * msort() can be used to sort a rowset like array on one or more
     * 'headers' (keys in the 2th array).
     *
     * @param array        $array      The array to sort.
     * @param string|array $key        The index(es) to sort the array on.
     * @param int          $sort_flags The optional parameter to modify the sorting
     *                                 behavior. This parameter does not work when
     *                                 supplying an array in the $key parameter.
     *
     * @return array The sorted array.
     */
    public static function msort($array, $key, $sort_flags = SORT_REGULAR, $preserveKey = false)
    {
        if (is_array($array) && count($array) > 0) {
            if (!empty($key)) {
                $mapping = array();
                foreach ($array as $k => $v) {
                    $sort_key = '';
                    if (!is_array($key)) {
                        $sort_key = $v[$key];
                    } else {
                        // @TODO This should be fixed, now it will be sorted as string
                        foreach ($key as $key_key) {
                            $sort_key .= $v[$key_key];
                        }
                        $sort_flags = SORT_STRING;
                    }
                    $mapping[$k] = $sort_key;
                }
                asort($mapping, $sort_flags);
                $sorted = array();
                foreach ($mapping as $k => $v) {
                    if ($preserveKey) {
                        $sorted[$k] = $array[$k];
                    } else {
                        $sorted[] = $array[$k];
                    }
                }
                return $sorted;
            }
        }
        return $array;
    }

    /**
     * get affiliate class
     *
     * @param string $string
     * @return <string>
     */
    public static function getAffiliateClass($string)
    {
        if ($string) {
            $string = strtolower($string);
            $class = array();
            $class['http://www.clickpets.co.uk'] = 'click-pets';
            $class['http://clickpets.co.uk'] = 'click-pets';
            $class['http://www.birdtrader.co.uk'] = 'bird-trader';
            $class['http://birdtrader.co.uk'] = 'bird-trader';
            $class['http://www.farmingads.co.uk'] = 'farming-items';
            $class['http://farmingads.co.uk'] = 'farming-items';
            $class['http://www.vansandtrucks.co.uk'] = 'vans-trucks';
            $class['http://vansandtrucks.co.uk'] = 'vans-trucks';
            $class['http://www.forklifttruckmart.co.uk'] = 'forklift-truck';
            $class['http://forklifttruckmart.co.uk'] = 'forklift-truck';
            $class['http://www.boatsandoutboards.co.uk'] = 'boats-n-outboards';
            $class['http://boatsandoutboards.co.uk'] = 'boats-n-outboards';
            $class['http://www.boatshop24.co.uk'] = 'affiliate-site';
            $class['http://boatshop24.co.uk'] = 'affiliate-site';
            $class['http://www.dogsandpuppies.co.uk'] = 'dogs-n-puppies';
            $class['http://dogsandpuppies.co.uk'] = 'dogs-n-puppies';
            $class['http://www.kittenads.co.uk'] = 'kitten-items';
            $class['http://kittenads.co.uk'] = 'kitten-items';
            $class['http://www.horsemart.co.uk'] = 'horse-mart';
            $class['http://horsemart.co.uk'] = 'horse-mart';
            $class['http://www.musicalads.co.uk'] = 'musical-items';
            $class['http://musicalads.co.uk'] = 'musical-items';
            $class['http://www.ukbike.com'] = 'ukbike';
            $class['http://ukbike.com'] = 'ukbike';
            $class['http://www.boatshop24.co.uk'] = 'boatshop24';
            $class['http://boatshop24.co.uk'] = 'boatshop24';
            $class['http://www.poultryads.co.uk'] = 'poultryitems';
            $class['http://poultryads.co.uk'] = 'poultryitems';
            $class['http://www.pigeonads.co.uk'] = 'pigeonitems';
            $class['http://pigeonads.co.uk'] = 'pigeonitems';
            $class['http://www.caravansforsale.co.uk'] = 'caravans';
            $class['http://caravansforsale.co.uk'] = 'caravans';
            $class['http://www.simplysalesjobs.co.uk'] = 'simplysalesjobs';
            $class['http://simplysalesjobs.co.uk'] = 'simplysalesjobs';
            $class['http://www.businessesforsale.com'] = 'businessforsale';
            $class['http://businessesforsale.com'] = 'businessforsale';
            $class['trade-it.co.uk'] = 'tradeit-list-logo';
            $class['wightbay.com'] = 'wightbay-list-logo';
            $class['http://buildersbay.co.uk'] = 'buildersbay-list-logo';
            if (isset($class[$string])) { 
                return $class[$string];
            } else {
                return 'affiliate-site';
            }
        }
    }

    /**
     * Get engine size range.
     *
     * @return array
     */
    public static function getEngineSizeChoices()
    {
        $engineSize = array(
            '50cc-under'  => '50cc and under',
            '51-125cc'    => '51-125cc',
            '126-250cc'   => '126-250cc',
            '251-400cc'   => '251-400cc',
            '401-500cc'   => '401-500cc',
            '501-600cc'   => '501-600cc',
            '601-800cc'   => '601-800cc',
            '801-1000cc'  => '801-1000cc',
            '1001-1200cc' => '1001-1200cc',
            'over-1200cc' => 'Over 1200cc'
        );

        return $engineSize;
    }

    /**
     * Get engine size range by value.
     *
     * @return boolean
     */
    public static function getEngineSizeRangeByValue($value)
    {
        if ($value <= 50) {
            return '50cc-under';
        } elseif ($value > 1200) {
            return 'over-1200cc';
        } else {
            $ranges = self::getEngineSizeChoices();
            unset($ranges['50cc-under'], $ranges['over-1200cc']);
            foreach ($ranges as $enginzeSizeRange => $enginzeSizeText) {
                list($min, $max) = explode('-', $enginzeSizeRange);
                $min = str_replace('cc', '', $min);
                $max = str_replace('cc', '', $max);

                if ($value >= $min && $value <= $max) {
                    return $enginzeSizeRange;
                }
            }
        }

        return null;
    }

    /**
     * Remove https from url.
     *
     * @param string $url Url string.
     *
     * @return string
     */
    public static function removeHttpsFromUrl($url)
    {
        $find    = array('http://', 'https://');
        $replace = array('');

        $url = str_replace($find, $replace, $url);

        return $url;
    }

    /**
     * Calculate net amount from gross amount.
     *
     * @param float  $grossAmount Gross amount.
     * @param object $container   Container identifier.
     *
     * @return string
     */
    public static function getNetAmountFromGrossAmount($grossAmount, $container)
    {
        $vatAmount = $container->get('doctrine')->getManager()->getRepository('FaCoreBundle:Config')->getVatAmount();
        $netAmount = ($grossAmount/(1+($vatAmount/100)));

        return $netAmount;
    }

    /**
     * Format number.
     *
     * @param integer/float $number Number.
     * @param string        $locale Locale.
     *
     * @return string
     */
    public static function formatNumber($number, $container, $locale = '')
    {
        if (!$locale) {
            $locale = $container->getParameter('locale');
        }

        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        //$formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
        //$formatter->setAttribute(\NumberFormatter::PAD_AFTER_PREFIX, 2);
        $formattedNumber = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);

        $formattedNumber = preg_replace('/(\.00)/', '', $formattedNumber);

        return $formattedNumber;
    }

    /**
     * Check whether to send email to user or not.
     *
     * @param unknown $userId    User id.
     * @param unknown $container Container object.
     *
     * @return boolean
     */
    public static function checkSendEmailToUser($userId, $container)
    {
        $userStatus = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserStatus($userId, $container);

        if ($userStatus == EntityRepository::USER_STATUS_ACTIVE_ID) {
            return true;
        }

        return false;
    }

    /**
     * Get location detail from parameter or cookie.
     *
     * @param string $location  Location slug.
     * @param object $request   Request object.
     * @param object $container Container object.
     *
     * @return array
     */
    public static function getLocationDetailFromParamsOrCookie($location, $request, $container)
    {
        $locationDetails = array();
        $location        = trim($location);
        if ($location) {
            $em = $container->get('doctrine')->getManager();
            $locationId = $em->getRepository('FaEntityBundle:Location')->getIdBySlug($location, $container);

            if (!$locationId) {
                $locationId = $em->getRepository('FaEntityBundle:Locality')->getColumnBySlug('id', $location, $container);
            }
            
            if ($locationId && $locationId != LocationRepository::COUNTY_ID) {
                $locationDetails = $em->getRepository('FaEntityBundle:Location')->getCookieValue($location, $container, true);
                
                if (!isset($locationDetails['location'])) {
                    $locationDetails = json_decode($request->cookies->get('location'), true);
                }
            }
        } else {
            $locationDetails = json_decode($request->cookies->get('location'), true);
        }

        return $locationDetails;
    }

    /**
     * This method is used to check whether the request is coming from bots or not.
     *
     * @param $container Container identifier.
     *
     * @return boolean
     */
    public static function isBot($container)
    {
        try {
            if ($container) {
                $userAgent = $container->get('request_stack')->getCurrentRequest()->headers->get('User-Agent');
                if ($userAgent) {
                    return $container->get('fa.bot.manager')->isBot($userAgent);
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Create Unique Arrays using an md5 hash
     *
     * @param array $array
     *
     * @return array
     */
    public static function arrayUnique($array, $preserveKeys = false)
    {
        // Unique Array for return
        $arrayRewrite = array();
        // Array with the md5 hashes
        $arrayHashes = array();
        foreach ($array as $key => $item) {
            // Serialize the current element and create a md5 hash
            $hash = md5(serialize($item));
            // If the md5 didn't come up yet, add the element to
            // to arrayRewrite, otherwise drop it
            if (!isset($arrayHashes[$hash])) {
                // Save the current element hash
                $arrayHashes[$hash] = $hash;
                // Add element to the unique Array
                if ($preserveKeys) {
                    $arrayRewrite[$key] = $item;
                } else {
                    $arrayRewrite[] = $item;
                }
            }
        }

        return $arrayRewrite;
    }

    /**
     * Get admin ad counter.
     *
     * @param object $container Container object.
     *
     * @return integer
     */
    public static function getAdminAdCounter($container)
    {
        if (!$container->get('session')->has('admin_ad_counter')) {
            $container->get('session')->set('admin_ad_counter', 1);
        } elseif ($container->get('session')->has('admin_ad_counter')) {
            $container->get('session')->set('admin_ad_counter', $container->get('session')->get('admin_ad_counter') + 1);
        }

        return $container->get('session')->get('admin_ad_counter');
    }

    /**
     * Strip unwanted tags and prevent XSS
     *
     * @param unknown $text
     * @param string $tags
     * @param string $invert
     * @return mixed|unknown
     */
    public static function stripTagsContent($text, $tags = '', $invert = false)
    {
        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);

        if (is_array($tags) and count($tags) > 0) {
            if ($invert == false) {
                return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            } else {
                return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
            }
        } elseif ($invert == false) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
    }

    /**
     * Return printable encoded string
     *
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function quoted_printable_encode($string, $encoding='UTF-8')
    {
        // use this function with headers, not with the email body as it misses word wrapping
        $len = strlen($string);
        $result = '';
        $enc = false;
        for ($i=0;$i<$len;++$i) {
            $c = $string[$i];
            if (ctype_alpha($c)) {
                $result.=$c;
            } elseif ($c==' ') {
                $result.='_';
                $enc = true;
            } else {
                $result.=sprintf("=%02X", ord($c));
                $enc = true;
            }
        }
        //L: so spam agents won't mark your email with QP_EXCESS
        if (!$enc) {
            return $string;
        }
        return '=?'.$encoding.'?q?'.$result.'?=';
    }

    /**
     * Get discount amount based on code type and value.
     *
     * @param object  $codeObj Code object.
     * @param integer $amount  Amount on which code is applied.
     *
     * @return number|boolean
     */
    public static function getDiscountAmount($codeObj, $amount)
    {
        if ($codeObj->getDiscountType() == PackageDiscountCodeRepository::PACKAGE_PERCENTAGE_DISCOUNT_TYPE_ID) {
            return (($amount * $codeObj->getDiscountValue()) / 100);
        }

        return false;
    }

    /**
     * Get discount amount based on code type and value.
     *
     * @param array   $codeObj Code array.
     * @param integer $amount  Amount on which code is applied.
     *
     * @return number|boolean
     */
    public static function getDiscountAmountFromArray($codeArray, $amount)
    {
        if ($codeArray['discount_type'] == PackageDiscountCodeRepository::PACKAGE_PERCENTAGE_DISCOUNT_TYPE_ID) {
            if (isset($codeArray['discount_given'])) {
                return $codeArray['discount_given'];
            } else {
                return (($amount * $codeArray['discount_value']) / 100);
            }
        } elseif ($codeArray['discount_type'] == PackageDiscountCodeRepository::PACKAGE_CASH_DISCOUNT_TYPE_ID) {
            return $codeArray['discount_given'];
        }

        return false;
    }

    /**
     * Get discount value to display.
     *
     * @param array $codeDetailArray Code detail array.
     *
     * @return string
     */
    public static function getDiscountValuetoDisplay($codeDetailArray)
    {
        if (isset($codeDetailArray['discount_value']) && isset($codeDetailArray['discount_type']) && $codeDetailArray['discount_type'] == PackageDiscountCodeRepository::PACKAGE_PERCENTAGE_DISCOUNT_TYPE_ID) {
            return $codeDetailArray['discount_value'].'%';
        } elseif (isset($codeDetailArray['discount_given']) && isset($codeDetailArray['discount_type']) && $codeDetailArray['discount_type'] == PackageDiscountCodeRepository::PACKAGE_CASH_DISCOUNT_TYPE_ID) {
            return '£'.$codeDetailArray['discount_given'];
        }

        return '';
    }

    /**
     * Hide or remove phone number.
     *
     * @param string $string String to search for phone number.
     * @param string $type   Flag either 'hide' or 'remove'.
     * @param string $suffix   Flag either 'AdDeatils' or 'Profile'.
     * @return string
     */
    public static function hideOrRemovePhoneNumber($string, $type,$pagetype, $suffix = null)
    {
        if ($type == 'remove') {
            return preg_replace("~(((\+44\s?\d{4}|\(?0\d{4}\)?)\s?\d{3}\s?\d{3})|((\+44\s?\d{3}|\(?0\d{3}\)?)\s?\d{3}\s?\d{4})|((\+44\s?\d{2}|\(?0\d{2}\)?)\s?\d{4}\s?\d{4}))(\s?\#(\d{4}|\d{3}))?~", '', $string);
        } elseif ($type == 'hide') {
            preg_match_all("~(((\+44\s?\d{4}|\(?0\d{4}\)?)\s?\d{3}\s?\d{3})|((\+44\s?\d{3}|\(?0\d{3}\)?)\s?\d{3}\s?\d{4})|((\+44\s?\d{2}|\(?0\d{2}\)?)\s?\d{4}\s?\d{4}))(\s?\#(\d{4}|\d{3}))?~", $string, $matches);

            $phoneNumberToBeReplaced = array();
            if (isset($matches[0]) && count($matches[0])) {
                foreach ($matches[0] as $index => $phoneNumber) {
                    $phoneNumberToBeReplaced[$index] = $phoneNumber;
                    if($pagetype == 'Profile') {
                        $GaClass = 'ga-callNowBusinessDesc';
                    }
                    elseif ($pagetype == 'AdDetails') {
                        $GaClass = 'ga-callNowAdDesc';
                    }
                    else{
                        $GaClass = '';
                    }
                    $string = preg_replace('/'.preg_quote($phoneNumber, '/').'/', '<span id="span_contact_number_full_desc_'.$suffix.$index.'" style="display:none;">#phoneNumberToBeReplaced'.$index.'</span><span id="span_contact_number_part_desc_'.$suffix.$index.'">'.substr($phoneNumber, 0, -2).'...<a class="'.$GaClass.'" href="javascript:toggleContactNumberForDesc(\''.$suffix.$index.'\');">(click to reveal full phone number)</a></span>', $string, 1);
                }
                foreach ($phoneNumberToBeReplaced as $index => $phoneNumber) {
                    $string = str_replace('#phoneNumberToBeReplaced'.$index, $phoneNumber, $string);
                }
            }

            return $string;
        }
    }

    /**
     * Hide or remove email.
     *
     * @param integer $adId   Ad id.
     * @param string  $string String to search for email.
     * @param string  $type   Flag either 'hide' or 'remove'.
     *
     * @return string
     */
    public static function hideOrRemoveEmail($adId, $string, $type,$pagetype, $suffix = null)
    {
        if ($type == 'remove') {
            return preg_replace("~[_a-zA-Z0-9-+]+(\.[_a-zA-Z0-9-+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,})~", '', $string);
        } elseif ($type == 'hide') {
            preg_match_all("~[_a-zA-Z0-9-+]+(\.[_a-zA-Z0-9-+]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,})?~", $string, $matches);

            if (isset($matches[0]) && count($matches[0])) {
                foreach ($matches[0] as $index => $email) {
                    $gaclass = '';
                    if($pagetype = 'AdDetails') {
                        $gaclass= 'ga-emailDescriptionAd';
                    }
                    elseif ($pagetype = 'Profile') {
                        $gaclass= 'ga-emailDescriptionBusiness';
                    }
                    $string = str_replace($email, '<a class="'.$gaclass.'" href="javascript:contactSeller(\''.$adId.'\', \'Email contact click (Description)\');">click to contact</a>', $string);
                }
            }

            return $string;
        }
    }

    /**
     * Inserts values before specific key.
     *
     * @param array $array
     * @param sting/integer $position
     * @param array $values
     * @throws Exception
     */
    public static function insertBeforeArray(array &$array, $position, array $values)
    {
        // enforce existing position
        if (!isset($array[$position])) {
            throw new \Exception(strtr('Array position does not exist (:1)', [':1' => $position]));
        }

        // offset
        $offset = -1;

        // loop through array
        foreach ($array as $key => $value) {
            // increase offset
            ++$offset;

            // break if key has been found
            if ($key == $position) {
                break;
            }
        }

        $array = array_slice($array, 0, $offset, true) + $values + array_slice($array, $offset, null, true);

        return $array;
    }

    /**
     * Set cache control headers
     *
     * @return response $response
     */
    public static function setCacheControlHeaders()
    {
        $response = new Response();

        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('max-age', 0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);

        return $response;
    }

    /**
     * Get converted number to word.
     *
     * @return string $string
     */
    public static function getConvertNumberToWords($number, $dictionaryNo = 1)
    {
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $decimal     = ' point ';
        $dictionary1  = array(
                0                   => 'zero',
                1                   => 'one',
                2                   => 'two',
                3                   => 'three',
                4                   => 'four',
                5                   => 'five',
                6                   => 'six',
                7                   => 'seven',
                8                   => 'eight',
                9                   => 'nine',
                10                  => 'ten',
                11                  => 'eleven',
                12                  => 'twelve',
                13                  => 'thirteen',
                14                  => 'fourteen',
                15                  => 'fifteen',
                16                  => 'sixteen',
                17                  => 'seventeen',
                18                  => 'eighteen',
                19                  => 'nineteen',
                20                  => 'twenty',
                30                  => 'thirty',
                40                  => 'fourty',
                50                  => 'fifty',
                60                  => 'sixty',
                70                  => 'seventy',
                80                  => 'eighty',
                90                  => 'ninety',
                100                 => 'hundred',
                1000                => 'thousand',
                1000000             => 'million',
                1000000000          => 'billion',
                1000000000000       => 'trillion',
                1000000000000000    => 'quadrillion',
                1000000000000000000 => 'quintillion'
        );

        $dictionary2  = array(
                0                   => 'zero',
                1                   => 'first',
                2                   => 'second',
                3                   => 'third',
                4                   => 'fourth',
                5                   => 'fifth',
                6                   => 'sixth',
                7                   => 'seventh',
                8                   => 'eightth',
                9                   => 'nineth',
                10                  => 'tenth',
                11                  => 'eleventh',
                12                  => 'twelveth',
                13                  => 'thirteenth',
                14                  => 'fourteenth',
                15                  => 'fifteenth',
                16                  => 'sixteenth',
                17                  => 'seventeenth',
                18                  => 'eighteenth',
                19                  => 'nineteenth',
                20                  => 'twentieth',
                30                  => 'thirtieth',
                40                  => 'fourtieth',
                50                  => 'fiftieth',
                60                  => 'sixtieth',
                70                  => 'seventieth',
                80                  => 'eightieth',
                90                  => 'ninetieth',
                100                 => 'hundredth',
                1000                => 'thousand',
                1000000             => 'million',
                1000000000          => 'billion',
                1000000000000       => 'trillion',
                1000000000000000    => 'quadrillion',
                1000000000000000000 => 'quintillion'
        );

        if ($dictionaryNo == 1) {
            $dictionary = $dictionary1;
        } else {
            $dictionary = $dictionary2;
        }

        if (!is_numeric($number)) {
            return false;
        }

        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                    'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                    E_USER_WARNING
            );
            return false;
        }

        if ($number < 0) {
            return $negative . self::getConvertNumberToWords(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . self::getConvertNumberToWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = self::getConvertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= self::getConvertNumberToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    /**
     * Extend logged in user's session length
     *
     * @param object $container Container identifier.
     */
    public static function extendLoggedInUserSessionLength($container)
    {
        try {
            if ($container->get('session')->has('extend_session') && $container->get('session')->get('extend_session')) {
                //ini_set('session.cookie_lifetime', 3600*24*8);
                //ini_set('session.gc_maxlifetime', 3600*24*8);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Add http or https to url
     *
     * @param string $url
     * @param string $replacewith
     *
     * @return Ambigous <string, mixed>
     */
    public static function addProtocolToUrl($url, $replacewith="https")
    {
        if (!preg_match("~^(?:ht)tps?://~i", $url)) {
            $url = str_replace('//', $replacewith.'://', $url);
        }

        return $url;
    }

    /**
     * Get image size
     *
     * @param string $url
     *
     * @return Ambigous <string, mixed>
     */
    public static function getImagesizeFromImageUrl($url)
    {
        return @getimagesize($url);
    }

    /**
     * Get user report boolean fields array.
     *
     * @return array
     */
    public static function getUserProfileReportBooleanFieldsArray()
    {
        $fieldsArray = array();
        $fieldsArray['user_logo']               = 'Profile image';
        $fieldsArray['banner_path']             = 'Banner image';
        $fieldsArray['company_welcome_message'] = 'Welcome message';
        $fieldsArray['company_address']         = 'Address';
        $fieldsArray['user_phone']              = 'Phone';
        $fieldsArray['website_link']            = 'Website link';
        $fieldsArray['about']                   = 'About';

        return $fieldsArray;
    }

    /**
     * Get user report fields choices.
     *
     * @return array
     */
    public static function getUserProfileReportBasicFieldsChoices()
    {
        $fieldsArray = array();

        $fieldsArray['username'] = 'Email address';
        $fieldsArray['role_id']  = 'User type';

        return $fieldsArray;
    }

    /**
     * Get user profile report fields choices.
     *
     * @return array
     */
    public static function getUserProfileReportFieldsChoices()
    {
        $fieldsArray = array_merge(self::getUserProfileReportBasicFieldsChoices(), self::getUserProfileReportBooleanFieldsArray());
        asort($fieldsArray);

        return $fieldsArray;
    }

    /**
     * Transforms an under_scored_string to a camelCasedOne
     */
    public static function camelize($scored)
    {
        return ucfirst(
            implode(
                '',
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode(
                            '_',
                            $scored
                        )
                    )
                )
            )
        );
    }

    /**
     * Send push notification message to user
     *
     * @param string $title     Title of notification
     * @param string $gaTitle   Ga title
     * @param string $url       Url of notification
     * @param object $user      User
     * @param object $container Container object
     *
     * @return mixed
     */
    public static function sendPushNotificationMessage($title, $gaTitle, $url, $user, $container)
    {
        try {
            $pushNotificationParams = $container->getParameter('push_notifications');
            $headings = array(
                "en" => 'Friday-Ad'
            );

            $contents = array(
                "en" => $title
            );

            $stringAppend = '?';
            if (strpos($url, '?') !== false) {
                $stringAppend = '&';
            }
            $gaTrackString = $stringAppend.'utm_source=(direct)&utm_campaign=Push-notification&utm_medium=(none)&utm_content='.$gaTitle;

            $fields = array(
                'app_id' => $pushNotificationParams['appId'],
                'filters' => array(array("field" => "tag", "key" => "userId", "relation" => "=", "value" => md5($user->getId()))),
                'contents' => $contents,
                'headings' => $headings,
                'url' => $url.$gaTrackString,
            );

            $fields = json_encode($fields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pushNotificationParams['api_url']."/notifications");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                'Authorization: Basic '.$pushNotificationParams['rest_api_key']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        } catch (\Exception $e) {
            self::sendErrorMail($container, 'Error: Problem in push notification: '.$title, $e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * Remove empty sub-folders from given path
     *
     * @param string $path   Path to check
     * @param object $output Output object.
     *
     * @return boolean
     */
    public static function removeEmptySubFolders($path, $output = null)
    {
        $empty=true;
        foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file) {
            if (is_dir($file)) {
                if (!self::RemoveEmptySubFolders($file)) {
                    $empty=false;
                }
            } else {
                $empty=false;
            }
        }

        if ($empty) {
            rmdir($path);
            if ($output) {
                $output->writeln('Empty folder removed: '.$path);
            }
        }

        return $empty;
    }

    /**
     * Switch position of 2 elements
     *
     * @param string $key1  Key 1.
     * @param string $key2  Key 2.
     * @param array  $array Array of elements.
     *
     * @return array
     */
    public static function arraySwapAssoc($key1, $key2, $array)
    {
        $newArray = array();
        foreach ($array as $key => $value) {
            if ($key == $key1) {
                $newArray[$key2] = $array[$key2];
            } elseif ($key == $key2) {
                $newArray[$key1] = $array[$key1];
            } else {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

    /**
     *
     * @param object $container            Container identifier.
     * @param object $objMessageAttachment Message Attachment object.
     *
     * @return string
     */
    public static function getMessageAttachmentUrl($container, $objMessageAttachment, $sharedUrl = false)
    {
        $fileExtension = substr(strrchr($objMessageAttachment->getOriginalFileName(), '.'), 1);
        $fileName      = $objMessageAttachment->getSessionId().'_'.$objMessageAttachment->getHash().'.'.$fileExtension;
        if ($sharedUrl) {
            //$attachmentUrl = $container->getParameter('fa.static.shared.url').'/'.$objMessageAttachment->getPath().'/'.$fileName;
            $attachmentUrl = $container->getParameter('base_url').'/'.$objMessageAttachment->getPath().'/'.$fileName;
        } else {
            $attachmentUrl = $container->getParameter('base_url').'/'.$objMessageAttachment->getPath().'/'.$fileName;
        }

        return $attachmentUrl;
    }

    public static function createZip($files = array(), $destination = '', $overwrite = false)
    {
        //if the zip file already exists and overwrite is false, return false
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        //vars
        $valid_files = array();
        //if files were passed in...
        if (is_array($files)) {
            //cycle through each file
            foreach ($files as $file) {
                //make sure the file exists
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if (count($valid_files)) {
            //create the archive
            $zip = new \ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            //add the files
            foreach ($valid_files as $file) {
                //$zip->addFile($file,$file);
                $zip->addFromString(basename($file), file_get_contents($file));
            }
            //debug
            //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

            //close the zip -- done!
            $zip->close();

            //check to make sure the file exists
            return file_exists($destination);
        } else {
            return false;
        }
    }

    /**
     * Download file.
     *
     * @param string $fileNameWithPath     file name with full path.
     * @param string $fileNameWhenDownload file name which we want to give when dowload.
     * @param string $forceDownload        download must not an option for open file if set to true.
     *
     * @return void
     */
    public static function downloadZipFile($fileNameWithPath, $fileNameWhenDownload, $forceDownload = false)
    {
        if (file_exists($fileNameWithPath)) {
            header('Content-Encoding: UTF-8');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="'.basename($fileNameWhenDownload).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileNameWithPath));
            header('Content-Type: application/zip');

            if ($forceDownload) {
                header('Content-Type: application/octet-stream');
            }

            //echo "\xEF\xBB\xBF";
            return readfile($fileNameWithPath);
        }
    }

    /**
     * Download file from http url.
     *
     * @param string $sourceUrl       source url.
     * @param string $destinationPath local destination path.
     *
     * @return void
     */
    public static function downloadFileByUrl($sourceUrl, $destinationPath)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sourceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        $file = fopen($destinationPath, "w+");
        fputs($file, $data);
        fclose($file);
    }

    /*
     *  Get mysql stop words.
    */
    public static function getMySqlStopWords()
    {
        return array(
            'about',
            'com',
            'for',
            'from',
            'how',
            'that',
            'the',
            'this',
            'was',
            'what',
            'when',
            'where',
            'who',
            'will',
            'with',
            'und',
            'the',
        );
    }

    /**
     * Return if array has consicutive same values.
     * @param array $array
     * @return boolean
     */
    public static function isConsicutiveSameValueInArray($array)
    {
        $prevValue = '';
        foreach ($array as $key => $value) {
            if ($value == $prevValue) {
                return true;
            }
            $prevValue = $value;
        }

        return false;
    }


    public static function checkFileExists($path)
    {
        //if(file_exists($path)) { return true; }
        //else { return false; }
        $file_headers = @get_headers($url);
        if ($file_headers[0] == 'HTTP/1.0 404 Not Found') { // or "HTTP/1.1 404 Not Found" etc.
            $file_exists = false;
        } else {
            $file_exists = true;
        }
        return $file_exists;
    }

    /*
     *  Get mysql stop words.
    */
    public static function getDistanceArray()
    {
        return array(0,2,5,10,15,20,30,50,75,100,150,200);
    }

    public static function getClosest($search)
    {
        $closest = null;
        $getDistanceArray = self::getDistanceArray();
        foreach ($getDistanceArray as $item) {
            if ($closest === null || abs($search - $closest) > abs($item - $search)) {
                $closest = $item;
            }
        }
        return $closest;
    }

    /**
     * Check if a given substring exists in the given string - Case sensitive.
     *
     * @param string $haystack
     * @param string $needle
     * @param bool $caseSensitive
     * @return bool
     */
    public function substr_exist($haystack, $needle, $caseSensitive = false)
    {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return !is_bool(strpos($haystack, $needle));
    }
}
