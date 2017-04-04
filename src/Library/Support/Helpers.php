<?php

use NeoFuture\Debug\Dumper;
use NeoFuture\Library\Router;
use NeoFuture\Library\Application;
/**
 * @param $thisKey
 * @param null $default
 * @return null
 */
function setup($thisKey, $default = null)
{
    if (!isset($GLOBALS['setup'])) {
        if (is_file(Application::getPath() . "/.setup")) {
            $config = file(Application::getPath() . "/.setup");
            foreach ($config as $setting) {
                if (preg_match("/=/", $setting)) {
                    list($key, $value) = explode("=", $setting);
                    if (trim($value) == "true") {
                        $GLOBALS['setup'][trim($key)] = true;
                    } elseif (trim($value) == "false") {
                        $GLOBALS['setup'][trim($key)] = false;
                    } else {
                        $GLOBALS['setup'][trim($key)] = trim($value);
                    }
                }
            }
        } else {
            echo ".setup file not found";
            exit;
        }
    }
    return (isset($GLOBALS['setup'][$thisKey]) ? $GLOBALS['setup'][$thisKey] : $default);
}

/**
 * Well this is probably what you are looking at this through... take the red pill !
 */
function wtf(...$var)
{
    echo (new Dumper)->output($var);
    die(1);
}

/**
 * @param $size
 * @return string
 */
function formatSize($size)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

/**
 * @param $seconds
 * @return string
 */
function formatDuration($seconds)
{
    if ($seconds < 0.001) {
        return round($seconds * 1000000) . ' μs';
    } elseif ($seconds < 1) {
        return round($seconds * 1000, 2) . ' ms';
    }
    return round($seconds, 2) . ' s';
}

/**
 * @param string $content
 * @param int $status
 * @param array $headers
 * @return \NeoFuture\Library\Response
 */
function response($content = '', $status = 200, array $headers = [])
{
    $response = new \NeoFuture\Library\Response($content, $status, $headers);
    return $response;
}

/**
 * @param $to
 */
function redirect($to)
{
    list($routes, $middleware) = Router::getRoutes();
    foreach($routes as $route){
        if(isset($route['name'])){
            if($route['name'] == $to){
                $to = (isset($route['group']) ? $route['group'] : '') . $route['pattern'];
                break;
            }
        }
    }

    echo "<script>document.location.href='" . $to . "';</script>";
    echo "Redirecting to: " . $to;
    exit;
}