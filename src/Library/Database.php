<?php

namespace NeoFuture\Library;

use NeoFuture\Library\Config;

/**
 * Class Database
 * @package NeoFuture\Library
 */
class Database
{
    private static $_instance = [];
    private static $_queryLog = [];

    /**
     * @return \PDO
     */
    private static function connect()
    {
        $opt = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$_instance = new \PDO("mysql:host=" . Config::get("database.host") .
            ";dbname=" . Config::get("database.name") .
            ";charset=" . Config::get("database.charset"),
            Config::get("database.username"),
            Config::get("database.password"),
            $opt);

        if (setup("DEBUG") == true) {
            self::$_instance->query('set profiling=1');
        }

        return self::$_instance;
    }

    /**
     * @return array|\PDO
     */
    private static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = self::connect();
        }
        return self::$_instance;
    }

    /**
     * @param $query
     * @param null $values
     * @return array
     */
    public static function query($query, $values = null)
    {
        if (isset($values) AND !is_array($values)) {
            $values = [$values];
        }
        $pdo = self::getInstance();

        $sth = $pdo->prepare($query);
        $sth->execute($values);

        if (setup("DEBUG") == true) {
            $res = $pdo->query('show profiles');
            $records = $res->fetchAll(\PDO::FETCH_ASSOC);
            $databaseName = $pdo->query('select database()')->fetchColumn();
            self::queryAddLog($records[count($records)-1], $values, $databaseName);
        }

        $results = $sth->fetchAll(\PDO::FETCH_OBJ);
        if (!isset($results[0])) {
            return false;
        }
        return count($results) > 1 ? $results : $results[0];
    }

    public static function raw($query)
    {
        $pdo = self::getInstance();

        $sth = $pdo->query($query);
        $lastId = $pdo->lastInsertId();

        if (setup("DEBUG") == true) {
            $res = $pdo->query('show profiles');
            $records = $res->fetchAll(\PDO::FETCH_ASSOC);
            $databaseName = $pdo->query('select database()')->fetchColumn();
            self::queryAddLog($records[count($records)-1], null, $databaseName);
        }

        $results = [];
        if (gettype($sth) != "boolean") {
            $results = $sth->fetchAll(\PDO::FETCH_OBJ);
        }

        if ($lastId) {
            return $lastId;
        } else {
            if (!isset($results[0])) {
                return false;
            }
            return count($results) > 1 ? $results : $results[0];
        }

    }

    /**
     * @param $id
     * @param $query
     * @param $duration
     */
    public static function queryAddLog($records, $values = null, $databaseName)
    {
        $key = count(static::$_queryLog);
        static::$_queryLog[$key]['query'] = $records['Query'];
        static::$_queryLog[$key]['duration'] = $records['Duration'];
        static::$_queryLog[$key]['values'] = $values;
        static::$_queryLog[$key]['database'] = $databaseName;
    }

    /**
     * @return array
     */
    public static function getQueryLog()
    {
        return static::$_queryLog;
    }

    /**
     * @return int
     */
    public static function getQueryCount()
    {
        return count(static::$_queryLog);
    }
}