<?php

namespace Fa\Bundle\CoreBundle\Factory;

class ObjectFactory
{
    static private $_registry = array();

    /**
    * Retrieve a value from registry by a key
    *
    * @param string $key registry key
    *
    * @return mixed
    */
    public static function registry($key)
    {
        if (isset(self::$_registry[$key])) {
            return self::$_registry[$key];
        }

        return null;
    }

    /**
    * Retrieve a value from registry by a key
    *
    * @param string $key   registry key
    * @param mixed  $value class object
    *
    * @return mixed
    */
    public static function register($key, $value)
    {
        if (!isset(self::$_registry[$key])) {
            self::$_registry[$key] = $value;
        }
    }

    /**
    * Unregister a variable from register by key
    *
    * @param string $key registry key
    *
    * @return void
    */
    public static function unregister($key)
    {
        if (isset(self::$_registry[$key])) {
            if (is_object(self::$_registry[$key]) && (method_exists(self::$_registry[$key], '__destruct'))) {
                self::$_registry[$key]->__destruct();
            }

            unset(self::$_registry[$key]);
        }
    }

    /**
   * Retrieve model object singleton
   *
   * @param string $modelClass model class name
   * @param array  $arguments  arguments array
   *
   * @return object
   */
    public static function getSingleton($modelClass = null, array $arguments = array())
    {
        $registryKey = $modelClass;
        if (!self::registry($registryKey)) {
            self::register($registryKey, self::getModel($modelClass, $arguments));
        }

        return self::registry($registryKey);
    }

     /**
    * Retrieve model object singleton
    *
    * @param string $modelClass model class name
    * @param array  $arguments  arguments array
    *
    * @return Mage_Core_Model_Abstract
    */
    public static function getModel($modelClass = null, array $arguments = array())
    {
        if (class_exists($modelClass)) {
            $classObj = new $modelClass($arguments);
            return $classObj;
        }
        return false;
    }
}
