<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This twig extension is used to add custom twig functions, filters etc.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class CoreExtension extends \Twig_Extension implements \Twig_Extension_InitRuntimeInterface
{
    /**
     * Container identifier.
     *
     * @var object
     */
    private $container;

    /**
     * Template variables
     *
     * @var array
     */
    public $variables = array();

    /**
     * Twig environment instance
     *
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Initializes the runtime environment.
     *
     * This is where you can load some file that contains filter functions for instance.
     *
     * @param Twig_Environment $environment The current Twig_Environment instance
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
                   new \Twig_SimpleFunction('sortable_link', array($this, 'getSortableLink'), array('is_safe' => array('html', 'needs_environment' => true))),
                   new \Twig_SimpleFunction('render_field', array($this, 'renderField')),
                   new \Twig_SimpleFunction('format_currency', array($this, 'formatCurrency')),
                   new \Twig_SimpleFunction('format_number', array($this, 'formatNumber')),
                   new \Twig_SimpleFunction('currency_symbol', array($this, 'getCurrencySymbol')),
                   new \Twig_SimpleFunction('md5', array($this, 'getMd5')),
                   new \Twig_SimpleFunction('fetch_repository', array($this, 'fetchRepository')),
                   new \Twig_SimpleFunction('trim_text', array($this, 'trimText')),
                   new \Twig_SimpleFunction('sortable_combo', array($this, 'getSortableCombo'), array('is_safe' => array('html', 'needs_environment' => true))),
                   new \Twig_SimpleFunction('fetch_container_instance',  array($this, 'fetchContainer')),
                   new \Twig_SimpleFunction('staticCall', array($this, 'staticCall')),
                   new \Twig_SimpleFunction('array_unserialize', array($this, 'arrayUnserialize')),
                   new \Twig_SimpleFunction('array_serialize', array($this, 'arraySerialize')),
                   new \Twig_SimpleFunction('set_variables', array($this, 'setVariables'), array('is_safe' => array('html', 'needs_environment' => true))),
                   new \Twig_SimpleFunction('get_variables', array($this, 'getVariables'), array('is_safe' => array('html', 'needs_environment' => true))),
                   new \Twig_SimpleFunction('get_substr_count', array($this, 'getSubstrCount')),
                   new \Twig_SimpleFunction('get_object_vars', array($this, 'getObjectVars')),
                   new \Twig_SimpleFunction('isConstantDefined', array($this, 'isConstantDefined')),
                   new \Twig_SimpleFunction('get_dimension_field_from_name', array($this, 'getDimensionFieldNameFromName')),
                   new \Twig_SimpleFunction('ucfirst', array($this, 'ucfirst')),
                   new \Twig_SimpleFunction('append_to_array_by_index', array($this, 'appendToArrayByIndex')),
                   new \Twig_SimpleFunction('strip_tags', array($this, 'stripTags')),
                   new \Twig_SimpleFunction('asset_exists', array($this, 'asset_exists')),
                   new \Twig_SimpleFunction('asset_url', array($this, 'asset_url')),
                   new \Twig_SimpleFunction('image_url', array($this, 'image_url')),
                   new \Twig_SimpleFunction('shared_url', array($this, 'shared_url')),
                   new \Twig_SimpleFunction('dump_data', array($this, 'dumpData')),
                   new \Twig_SimpleFunction('unset_value_from_array', array($this, 'unsetValueFromArray')),
                   new \Twig_SimpleFunction('static_asset_url', array($this,'static_asset_url')),
                   new \Twig_SimpleFunction('array_unique', array($this, 'arrayUnique')),
                   new \Twig_SimpleFunction('replace_value_in_array', array($this, 'replaceValueInArray')),
                   new \Twig_SimpleFunction('replace_case_insensitive', array($this, 'replaceCaseInsensitive')),
                   new \Twig_SimpleFunction('hash_hmac', array($this, 'hashHmac')),
                   new \Twig_SimpleFunction('msort', array($this, 'multisort')),
               );
    }
    
    /* public function getGlobals()
    {
        return array(
            'fetch_container_instance' => new \Twig_SimpleFunction('fetch_container_instance', 'fetchContainer')
        );
    } */

    /**
     * Generates the url for a given page in a pagerfanta instance.
     *
     * @param string $route       Route name.
     * @param string $sortField   Sort field name.
     * @param string $fieldLabel  Field label.
     * @param array  $sortParams  Sorting paramerters.
     * @param array  $routeParams Routing paramerters.
     *
     * @return string
     */
    public function getSortableLink($route, $sortField, $fieldLabel, $sortParams = array(), $routeParams = array())
    {
        $options = array('route'       => $route,
                         'sortField'   => $sortField,
                         'fieldLabel'  => $fieldLabel,
                         'sorter'      => $sortParams,
                         'routeParams' => $routeParams
                   );

        return $this->environment->render('FaCoreBundle::sortableLink.html.twig', $options);
    }

    /**
     * Render field either from object or array.
     *
     * @param mixed  $table  Object or array.
     * @param string $field  Field name.
     *
     * @return string
     */
    public function renderField($table, $field)
    {
        $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (is_object($table) && method_exists($table, $methodName) === true) {
            return call_user_func(array($table, $methodName));
        } elseif (is_array($table) && isset($table[$field])) {
            return $table[$field];
        }
    }

    /**
     * Format price with currency.
     *
     * @param integer/float $price  Object or array.
     * @param string        $locale Locale.
     *
     * @return string
     */
    public function formatCurrency($price, $locale = '')
    {
        return CommonManager::formatCurrency($price, $this->container, $locale);
    }

    /**
     * Format number.
     *
     * @param integer/float $number Number.
     * @param string        $locale Locale.
     *
     * @return string
     */
    public function formatNumber($number, $locale = '')
    {
        return CommonManager::formatNumber($number, $this->container, $locale);

        if (!$locale) {
            $locale = $this->container->getParameter('locale');
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
     * Get currency symbol.
     *
     * @param string $locale Locale.
     *
     * @return string
     */
    public function getCurrencySymbol($locale = '')
    {
        return CommonManager::getCurrencySymbol($locale, $this->container);
    }

    /**
     * Get md5 of string.
     *
     * @param string $string Locale.
     *
     * @return string
     */
    public function getMd5($string = '')
    {
        return md5($string);
    }

    /**
     * Get var_dump of a variable
     *
     * @param string $expression Locale.
     *
     * @return string
     */
    public function dumpData($expression)
    {
        return var_dump($expression);
    }

    /**
     * Get repository.
     *
     * @param string $repositoryName Repository name.
     *
     * @return object
     */
    public function fetchRepository($repositoryName, $em = null)
    {
        return $this->container->get('doctrine')->getManager($em)->getRepository($repositoryName);
    }

    /**
     * Get container.
     *
     * @return object
     */
    public function fetchContainer()
    {
        return $this->container;
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
    public function trimText($text, $length = 10, $sign = '...', $stripHtml = true)
    {
        return CommonManager::trimText($text, $length, $sign, $stripHtml);
    }

    /**
     * Generates the url for a given page in a pagerfanta instance.
     *
     * @param string $route      Route name.
     * @param array  $sortField  Sort field name array.
     *
     * @return string
     */
    public function getSortableCombo($route, $sortField)
    {
        $options = array('route'      => $route,
            'sortField'  => $sortField,
        );

        return $this->environment->render('FaCoreBundle::sortableCombo.html.twig', $options);
    }

    /**
     * Static call.
     *
     * @param string $class    Class name.
     * @param string $function Function name
     * @param array  $args     Function arguments.
     */
    public function staticCall($class, $function, $args = array())
    {
        if (class_exists($class) && method_exists($class, $function)) {
            return call_user_func_array(array($class, $function), $args);
        }

        return null;
    }

    /**
     * Retrun extension name.
     *
     * @return string
     */
    public function getName()
    {
        return 'core_extension';
    }

    /**
     * Unserialize array.
     *
     * @param string $array Array of parameters.
     *
     * @return array
     */
    public function arrayUnserialize($array)
    {
        return unserialize($array);
    }

    /**
     * Serialize array.
     *
     * @param string $array Array of parameters.
     *
     * @return array
     */
    public function arraySerialize($array)
    {
        return serialize($array);
    }

    /**
     * Unique array.
     *
     * @param array $array Array of parameters.
     *
     * @return array
     */
    public function arrayUnique($array)
    {
        return array_unique($array);
    }


    /**
     * Set variables.
     *
     * @param string $key       Key.
     * @param array  $variables Twig variables.
     */
    public function setVariables($key, $variables = array())
    {
        $this->variables[$key] = $variables;
    }

    /**
     * Get variables.
     *
     * @param string $key Key.
     *
     * @return array
     */
    public function getVariables($key)
    {
        return isset($this->variables[$key]) ? $this->variables[$key] : null;
    }

    /**
     * Get substring count.
     *
     * @param string $string    String.
     * @param string $substring Sub string to be searched.
     */
    public function getSubstrCount($string, $substring)
    {
        return substr_count($string, $substring);
    }

    /**
     * Get array converted from object.
     *
     * @param object $object Object.
     */
    public function getObjectVars($object)
    {
        return get_object_vars($object);
    }

    /**
     * Check if given constant is defined or not.
     *
     * @param string $constant Constant.
     */
    public function isConstantDefined($constant)
    {
        if ($constant && defined($constant)) {
            return true;
        }

        return false;
    }

    /**
     * Get dimension field from name.
     *
     * @param string $dimensionName    Dimension field name.
     * @param string $rootCategoryName Root category name.
     *
     */
    public function getDimensionFieldNameFromName($dimensionName, $rootCategoryName, $searchType)
    {
        $dimensionField = str_replace(array('(', ')', ',', '?', '|', '.', '/', '\\', '*', '+', '-', '"', "'"), '', $dimensionName);
        $dimensionField = str_replace(' ', '_', strtolower($dimensionField));

        $searchTypeArray = explode('_', $searchType);
        if ($searchTypeArray[0] == 'choice') {
            if (!in_array($dimensionField, array('reg_year', 'mileage', 'engine_size'))) {
                $dimensionField = $dimensionField.'_id';
            }

            if ($dimensionField == 'mileage') {
                $dimensionField = 'mileage_range';
            }

            if ($dimensionField == 'engine_size') {
                $dimensionField = 'engine_size_range';
            }

            if ($dimensionField == 'ad_type_id') {
                $dimensionField = 'item__'.$dimensionField;
            } else {
                $dimensionField = 'item_'.$rootCategoryName.'__'.$dimensionField;
            }
        } else {
            $dimensionField = 'item_'.$rootCategoryName.'__'.$dimensionField;
        }

        return $dimensionField;
    }

    /**
     * Get first letter capital.
     *
     * @param string $string String.
     *
     * @return string
     */
    public function ucfirst($string)
    {
        return ucfirst($string);
    }

    /**
     * Merger array.
     *
     * @param string $array1 Array one.
     * @param string $array2 Array two.
     *
     * @return array
     */
    public function appendToArrayByIndex($array1, $array2, $index)
    {
        $array1[$index] = $array1[$index] + $array2;

        return $array1;
    }

    /**
     * Replace html tags.
     *
     * @param string $string       String to replace value.
     * @param string $allowedTags  Allowed tags.
     *
     * @return string
     */
    public function stripTags($string, $allowedTags)
    {
        return strip_tags($string, $allowedTags);
    }

    /**
     * Check file exist or not.
     *
     * @param string $path Asset path.
     * @return boolean
     */
    public function asset_exists($path)
    {
        $webRoot  = realpath($this->container->get('kernel')->getRootDir() . '/../web/');
        $filePath = realpath($webRoot.'/'.$path);

        // check if the file exists
        if (!file_exists($filePath)) {
            return false;
        }

        return true;
    }

    /**
     * Check file exist or not.
     *
     * @param string $path Asset path.
     * @return string
     */
    public function asset_url($path)
    {
        return $this->container->getParameter('fa.static.url').'/'.$path;
    }

    /**
     * Check file exist or not.
     *
     * @param string $path Asset path.
     * @return string
     */
    public function static_asset_url($path)
    {
        return $this->container->getParameter('fa.static.asset.url').$path;
    }

    /**
     * Check file exist or not.
     *
     * @param string $path shared path.
     * @return string
     */
    public function shared_url($path)
    {
        return $this->container->getParameter('fa.static.shared.url').'/'.$path;
    }

    /**
     * Check file exist or not.
     *
     * @param string $path image path.
     * @return string
     */
    public function image_url($path)
    {
        return $this->container->getParameter('fa.static.aws.url').'/'.$path;
    }

    /**
     * Check file exist or not.
     *
     * @param string $path image path.
     * @return string
     */
    public function unsetValueFromArray($array, $value)
    {
        $key = array_search($value, $array);
        if ($key !== false) {
            unset($array[$key]);
        }

        return $array;
    }

    /**
     * replaceValueInArray.
     *
     * @param array  $array    Array.
     * @param string $index    Index to be replaced.
     * @param string $newValue New value for index.
     *
     * @return array
     */
    public function replaceValueInArray($array, $index, $newValue)
    {
        if (isset($array[$index])) {
            $array[$index] = $newValue;
        }

        return $array;
    }

    /**
     * replaceCaseInsensitive.
     *
     * @param mixed $search       Search Array.
     * @param mixed $replace      Replace arra.
     * @param mixed $searchString Search string.
     *
     * @return array
     */
    public function replaceCaseInsensitive($search, $replace, $searchString)
    {
        return str_ireplace($search, $replace, $searchString);
    }

    /**
     * replaceValueInArray.
     *
     * @param string $algo
     * @param string $data
     * @param string $key
     *
     * @return array
     */
    public function hashHmac($algo, $data, $key)
    {
        return hash_hmac($algo ,$data, $key);
    }

    /**
     * multisort.
     *
     * @param array $array
     * @param string $key
     *
     * @return array
     */
    public function multisort($array, $key)
    {
        return CommonManager::msort($array, $key, 0, true);
    }
}
