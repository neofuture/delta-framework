<?php

namespace NeoFuture\Library;

use NeoFuture\Library\Database;
use NeoFuture\Library\Cipher;
use NeoFuture\Library\Support\Filesystem;
//use Predis;
use NeoFuture\Library\Support\Redis;

/**
 * Class Session
 * @package NeoFuture\Library
 */
class Session
{
    public static $session;
    public static $_instance;
    public static $_redis;

    protected static $_sessionDrivers = ["file", "database", "redis"];

    /**
     * Session constructor.
     */
    function __construct()
    {
        self::checkDriver(Config::get("session.driver"));

        if (!isset($_COOKIE[Config::get("session.key")])) {
            $key = microtime();
            $cookieValue = md5($key);
            setcookie(Config::get("session.key"), $cookieValue, time() + Config::get("session.ttl"));
        } else {
            $cookieValue = $_COOKIE[Config::get("session.key")];
        }

        if (Config::get("session.driver") == "file") {
            $session = Cipher::decrypt(Filesystem::open(Config::get("app.prefix") . "-cache", $cookieValue));

        } elseif (Config::get("session.driver") == "database") {

            $session = Database::query("SELECT store FROM sessions WHERE id = ? LIMIT 1", [$cookieValue]);

        } elseif (Config::get("session.driver") == "redis") {

            $session = Cipher::decrypt(Redis::get(Config::get("app.prefix") . ":session:" . $_COOKIE[Config::get("session.key")]));

        }

        if (isset($session->store)) {

            Session::boot(Cipher::decrypt($session->store));

        } else {

            Session::boot($session);

        }
    }

    /**
     * Session destructor.
     */
    function __destruct()
    {
        self::checkDriver(Config::get("session.driver"));

        if (isset($_COOKIE[Config::get("session.key")])) {
            $session = Session::getSession();

            if (Config::get("session.driver") == "file") {

                Filesystem::save(Config::get("app.prefix") . "-cache", $_COOKIE[Config::get("session.key")], Cipher::encrypt($session), Config::get("session.ttl"));

            } elseif (Config::get("session.driver") == "database") {

                Database::query("DELETE FROM sessions WHERE id = ?", $_COOKIE[Config::get("session.key")]);

                if (isset($session)) {
                    Database::query("INSERT INTO sessions SET id = ?, store = ?, registered = ?, ttl = ? ", [$_COOKIE[Config::get("session.key")], Cipher::encrypt($session), time(), Config::get("session.ttl")]);
                }

            } elseif (Config::get("session.driver") == "redis") {
                if (isset($session)) {

                    Redis::set(Config::get("app.prefix") . ":session:" . $_COOKIE[Config::get("session.key")], Cipher::encrypt($session), Config::get("session.ttl"));

                }
            }
        }
    }


    private static function checkDriver($driver)
    {
        if (!in_array($driver, self::$_sessionDrivers)) {

            throw new \Exception("Invalid Session Driver, Please check Config/session.php or your .setup file");

        }
        return true;
    }

    /**
     *
     */
    public static function start()
    {
        self::getInstance();
    }

    /**
     * @return Session
     *
     * Create a new session from a static context
     * and return it
     *
     */
    private static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new Session();
        }

        return self::$_instance;
    }

    /**
     * @return mixed
     */
    public static function getSession()
    {
        return self::$session;
    }

    /**
     * @param $session
     */
    public static function boot($session)
    {
        static::$session = $session;
    }

    public static function has($key)
    {
        self::getInstance();

        return isset(static::$session[$key]);
    }

    /**
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        self::getInstance();

        static::$session[$key] = $value;
    }

    /**
     * @param $key
     */
    public static function delete($key)
    {
        self::getInstance();

        unset(static::$session[$key]);
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        self::getInstance();

        if (!isset(static::$session[$key])) {
            return false;
        }

        return static::$session[$key];
    }

    /**
     *
     */
    public static function destroyAll()
    {

        self::checkDriver(Config::get("session.driver"));

        if (isset(self::$session)) {
            self::$session = null;
        }

        if (Config::get("session.driver") == "file") {
            Filesystem::delete("session", $_COOKIE[Config::get("session.key")]);

            if ($_COOKIE[Config::get("session.key")]) {
                setcookie(Config::get("session.key"), null, -1);
            }

        } elseif (Config::get("session.driver") == "database") {
            Database::query("DELETE FROM sessions WHERE id = ?", $_COOKIE[Config::get("session.key")]);

            if ($_COOKIE[Config::get("session.key")]) {
                setcookie(Config::get("session.key"), null, -1);
            }

        }
    }

    public static function all()
    {
        return self::getAll();
    }

    /**
     * @return bool
     */
    public static function getAll()
    {
        if (!isset(self::$session)) {
            return false;
        }
        return self::$session;
    }

}