<?php

namespace NeoFuture\Library;

use NeoFuture\Library\Support\Filesystem;
use NeoFuture\Library\Database;
use NeoFuture\Library\Cipher;
use NeoFuture\Library\Support\Redis;

/**
 * Class Database
 * @package NeoFuture\Library
 */
class Cache
{
    protected static $_cacheDrivers = ["file", "database", "redis"];

    private static function checkDriver($driver)
    {
        if(!in_array($driver, self::$_cacheDrivers)){
            throw new \Exception("Invalid Cache Driver, Please check Config/cache.php or your .setup file");
        }
        return true;
    }

    public static function remember($key, $value, $ttl)
    {

        self::checkDriver(Config::get("cache.driver"));

        $returnValue = null;
        if (Config::get("cache.driver") == "file") {

            $returnValue = Filesystem::open(Config::get("app.prefix") . "-cache", $key);
            $returnValue = Cipher::decrypt($returnValue);

        } elseif (Config::get("cache.driver") == "database") {

            $return = Database::query("SELECT store FROM cache WHERE id = ? ", Config::get("app.prefix") . "-cache-" . $key);
            if ($return) {
                $returnValue = Cipher::decrypt($return->store);
            }

        } elseif (Config::get("cache.driver") == "redis") {

            $returnValue = Cipher::decrypt(Redis::get(Config::get("app.prefix") . ":cache:" . $key));
        }

        if($returnValue != null){
             return unserialize($returnValue);
        }

        if (is_object($value) && ($value instanceof \Closure)) {
            $value = call_user_func($value);
        }

        $value = serialize($value);

        if (Config::get("cache.driver") == "file") {

            Filesystem::save(Config::get("app.prefix") . "-cache", $key, Cipher::encrypt($value), $ttl);

        } elseif (Config::get("cache.driver") == "database") {

            $return = Database::query("SELECT store FROM cache WHERE id = ? ", Config::get("app.prefix") . "-cache-" . $key);
            if ($return) {
                $value = Cipher::decrypt($return->store);
            } else {
                self::forget($key);
                Database::query("INSERT INTO cache SET id = ?, store = ?, registered = ?, ttl = ? ", [Config::get("app.prefix") . "-cache-" . $key, Cipher::encrypt($value), time(), $ttl]);
            }

        } elseif (Config::get("cache.driver") == "redis") {
            $return = Redis::get(Config::get("app.prefix") . ":cache:" . $key);
            if($return){
                $value = Cipher::decrypt($return);

            } else {
                self::forget(Config::get("app.prefix") . ":cache:" . $key);
                Redis::set(Config::get("app.prefix") . ":cache:" . $key, Cipher::encrypt($value), $ttl);
            }
        }

        return unserialize($value);
    }

    public static function rememberForever($key, $value){
        return self::remember($key, $value, -1);
    }

    public static function forget($key)
    {
        self::checkDriver(Config::get("cache.driver"));
        if (Config::get("cache.driver") == "file") {
            Filesystem::delete("cache",  Config::get("app.prefix") . ":cache:" . $key);

        } elseif (Config::get("cache.driver") == "database") {
            Database::query("DELETE FROM cache WHERE id = ? ", Config::get("app.prefix") . "-cache-" . $key);

        } elseif (Config::get("cache.driver") == "redis") {
            Redis::forget(Config::get("app.prefix") . ":cache:" . $key);
        }
        return true;
    }


}