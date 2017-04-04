<?php

namespace NeoFuture\Library;

class Application
{
    public static $path;
    public function __construct($path)
    {
        static::$path = $path;

        return $this;
    }

    public static function getPath()
    {
        return static::$path;
    }

    public function make($class, $application){
        $factory = new $class;
        $factory->application = $application;
        return $factory;
    }
}