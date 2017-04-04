<?php

namespace NeoFuture\Library\Support;

use Predis;
use NeoFuture\Library\Config;

/**
 * Class Redis
 * @package NeoFuture\Library\Support
 */
class Redis
{
    /**
     * @var array
     */
    private static $_instance = [];

    /**
     * @return array|Predis\Client
     */
    private static function connect()
    {
        Predis\Autoloader::register();

        self::$_instance = new Predis\Client(array(
            "scheme" => "tcp",
            "host" => Config::get("session.redis.host"),
            "port" => Config::get("session.redis.port")
        ));

        return self::$_instance;
    }

    /**
     * @return array|Predis\Client
     */
    private static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = self::connect();
        }
        return self::$_instance;
    }

    /**
     * @param $key
     * @param $value
     * @param $ttl
     */
    public static function set($key, $value, $ttl)
    {
        $redis = self::getInstance();

        $redis->set($key, $value);
        if ($ttl > 0) {
            $redis->expire($key, $ttl);
        }
    }

    /**
     * @param $key
     * @return string
     */
    public static function get($key)
    {
        $redis = self::getInstance();

        return $redis->get($key);
    }

    /**
     * @param $key
     */
    public static function forget($key)
    {
        $redis = self::getInstance();
        $redis->expire($key, 0);
    }
}