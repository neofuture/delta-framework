<?php

namespace NeoFuture\Library;

use \Application\Http\Kernel;
use NeoFuture\Library\Application;
/**
 * Class Router
 * @package NeoFuture\Library
 */
class Router
{
    private static $_routes = [];
    private static $_routeCount = 0;
    private static $_group = [];
    private static $_middleware = [];
    private static $_routeAdjust = 0;

    /**
     *
     */
    public static function registerRoutes()
    {
        include Application::getPath() . "/Application/Http/Routes.php";
    }


    /**
     * @param $_group
     * @param $callback
     */
    public static function group($_group, $callback)
    {

        static::$_group[count(static::$_group)] = $_group;

        if (is_array($callback)) {
            if ($callback['middleware']) {
                static::$_middleware[$_group] = $callback['middleware'];
            }

            if (is_callable($callback[0])) {
                $callback = $callback[0];
            }
        }

        call_user_func($callback);

        unset(static::$_group[(count(static::$_group) - 1)]);
    }

    /**
     * @param $pattern
     * @param $callback
     * @param $type
     */
    public static function addRoute($pattern, $callback, $type)
    {
        $count = static::$_routeCount;

        $route = [];

        if (is_array($callback)) {
            if ($callback['middleware']) {
                $route['middleware'] = $callback['middleware'];
            }

            if (is_callable($callback[0])) {
                $callback = $callback[0];
            }
        }

        $route['group'] = '';
        if (isset(static::$_group[0])) {
            $route['group'] = join(static::$_group);
        }

        $route['pattern'] = $pattern;
        $route['callback'] = $callback;
        $route['type'] = $type;

        static::$_routes[$count] = $route;

        static::$_routeCount++;
    }

    /**
     * @param $pattern
     * @param $callback
     */
    public static function any($pattern, $callback)
    {
        self::addRoute($pattern, $callback, "ANY");
        static::$_routeAdjust = 1;
        return new static;
    }

    /**
     * @param $pattern
     * @param $callback
     * @return static
     */
    public static function put($pattern, $callback)
    {
        self::addRoute($pattern, $callback, "PUT");
        static::$_routeAdjust = 1;
        return new static;
    }

    /**
     * @param $pattern
     * @param $callback
     * @return static
     */
    public static function delete($pattern, $callback)
    {
        self::addRoute($pattern, $callback, "DELETE");
        static::$_routeAdjust = 1;
        return new static;
    }

    /**
     * @param $pattern
     * @param $callback
     */
    public static function get($pattern, $callback)
    {
        self::addRoute($pattern, $callback, "GET");
        static::$_routeAdjust = 1;
        return new static;
    }

    /**
     * @param $pattern
     * @param $callback
     */
    public static function post($pattern, $callback)
    {
        self::addRoute($pattern, $callback, "POST");
        static::$_routeAdjust = 1;
        return new static;
    }

    /**
     * @param $match
     * @param $pattern
     * @param $callback
     * @return bool|static
     */
    public static function match($match, $pattern, $callback)
    {
        if (!isset($match)) {
            return false;
        }
        foreach ($match as $item) {
            self::addRoute($pattern, $callback, strtoupper($item));
        }
        static::$_routeAdjust = count($match);
        return new static;
    }

    /**
     * @param $original
     * @param $replacement
     */
    public function where($original, $replacement = null)
    {

        $amount = static::$_routeAdjust;

        for ($i = 1; $i <= $amount; $i++) {
            $route = static::$_routes[(static::$_routeCount - $i)];
            $pattern = $route['pattern'];

            if (is_array($original)) {
                foreach ($original as $key => $value) {
                    $pattern = str_replace("{" . $key . "}", "(" . self::cleanRegex($value) . ")", $pattern);
                }
            } else {
                $pattern = str_replace("{" . $original . "}", "(" . self::cleanRegex($replacement) . ")", $pattern);
            }
            static::$_routes[(static::$_routeCount - $i)]['pattern'] = $pattern;
        }

    }

    public function name($name)
    {
        $amount = static::$_routeAdjust;
        for ($i = 1; $i <= $amount; $i++) {
            static::$_routes[(static::$_routeCount - $i)]['name'] = $name;
        }
    }

    /**
     * @param $reg
     * @return mixed
     */
    private function cleanRegex($reg)
    {
        $reg = str_replace("(", "", $reg);
        $reg = str_replace(")", "", $reg);
        return $reg;
    }

    /**
     * @param $url
     * @return mixed
     */
    public static function execute($url)
    {

        self::registerRoutes();

        foreach (static::$_routes as $key => $route) {
            if ($route['type'] == $_SERVER['REQUEST_METHOD'] OR $route['type'] == "ANY") {

                $pattern = preg_replace_callback("/\{(.*?)\}/",
                    function ($matches) {
                        return "(.+?)";
                    },
                    $route['group'] . $route['pattern']
                );

                $pattern = '/^' . str_replace("/", "\/", $pattern) . '$/';

                if (preg_match($pattern, $url, $params)) {

                    if (is_string($route['callback']) && strpos($route['callback'], '@')) {
                        list($class, $method) = explode("@", $route['callback']);

                        array_shift($params);

                        self::loadMiddleware($route);

                        $qualifiedClass = '\Application\Http\Controllers\\' . $class;
                        return call_user_func_array([(new $qualifiedClass), $method], array_values($params));

                    } else {
                        array_shift($params);

                        self::loadMiddleware($route);

                        return call_user_func_array($route['callback'], array_values($params));
                    }
                }
            }
        }
        return false;
    }

    public static function loadMiddleware($route)
    {
        $middlewareList = [];
        if (isset(static::$_middleware[$route['group']])) {
            if (is_array(static::$_middleware[$route['group']])) {
                foreach (static::$_middleware[$route['group']] as $middleware) {
                    if (isset(Kernel::$_groupMiddleware[$middleware])) {
                        $middlewareList[$middleware] = Kernel::$_groupMiddleware[$middleware];
                    }
                }
            } else {
                if (isset(Kernel::$_groupMiddleware[static::$_middleware[$route['group']]])) {
                    $middlewareList[static::$_middleware[$route['group']]] = Kernel::$_groupMiddleware[static::$_middleware[$route['group']]];
                }
            }
        }

        if (isset($route['middleware'])) {
            if (is_array($route['middleware'])) {
                foreach ($route['middleware'] as $middleware) {
                    if (isset(Kernel::$_routeMiddleware[$middleware])) {
                        $middlewareList[$middleware] = Kernel::$_routeMiddleware[$middleware];
                    }
                }
            } else {
                if (isset(Kernel::$_routeMiddleware[$route['middleware']])) {
                    $middlewareList[$route['middleware']] = Kernel::$_routeMiddleware[$route['middleware']];
                }
            }
        }

        foreach ($middlewareList as $middleware) {
            call_user_func([(new $middleware), "handle"]);
        }
    }

    /**
     * @return array
     */
    public
    static function getRoutes()
    {
        return [static::$_routes, static::$_middleware];
    }
}