<?php

namespace NeoFuture\Library;

use NeoFuture\Library\Application;
/**
 * Class Cache
 * @package NeoFuture\Library
 */
class Config
{
    /**
     * @var array
     */
    private static $files = [];
    /**
     * @param $thisKey
     * @return bool
     */
    public static function get($thisKey)
    {
        $keys = explode(".", $thisKey);
        $folders = [];

        while(!is_file(Application::getPath() . "/Config/" . join("/", $folders) . $keys[0] . '.php')){
            $folders[] = array_shift($keys)."/";
        }

        $file = array_shift($keys);


        if (!isset(self::$files[join("/", $folders) . $file])) {
            $configFile = Application::getPath() . "/Config/" . join("/", $folders) . $file . '.php';

            if (!is_file($configFile)) {
                return false;
            }

            self::$files[join("/", $folders) . $file] = require($configFile);
        }

        $config = self::$files[join("/", $folders) . $file];

        if (isset($keys)) {
            foreach ($keys as $key => $val){
                if(isset($config[$val])){
                    $config = $config[$val];
                } else {
                    return false;
                }
            }
        }

        return $config;
    }

    /**
     * @return array
     */
    public static function getAll(){
        return self::$files;
    }
}