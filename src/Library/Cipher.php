<?php

namespace NeoFuture\Library;

use NeoFuture\Library\Config;

/**
 * Class Cipher
 * @package NeoFuture\Library
 */
class Cipher
{
    /**
     * @var string
     */
    private static $mode = 'AES-128-CBC';

    /**
     * @param $buffer
     * @return string
     */
    public static function encrypt($buffer)
    {

        $key = Config::get("cipher.key");
        $iv = Config::get("cipher.initialisationVector");
        $encrypt = openssl_encrypt(serialize($buffer), static::$mode, substr($key, 0, 16), 0, $iv);
        if(Config::get("cipher.enabled")) {
            return $encrypt;
        }
        return serialize($buffer);
    }

    /**
     * @param $buffer
     * @return mixed
     */
    public static function decrypt($buffer)
    {
        $key = Config::get("cipher.key");
        $iv = Config::get("cipher.initialisationVector");
        $decrypt = unserialize(openssl_decrypt($buffer, static::$mode, substr($key, 0, 16), 0, $iv));
        if(Config::get("cipher.enabled")){
            return $decrypt;
        }
        return unserialize($buffer);


    }
}
