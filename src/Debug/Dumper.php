<?php

namespace NeoFuture\Debug;

use NeoFuture\Library\Router;
use NeoFuture\Library\Config;
use NeoFuture\Library\Database;
use NeoFuture\Library\SqlFormatter;
use NeoFuture\Library\Session;
// - use NeoFuture\Psr4AutoloaderClass;

/**
 * Class Dumper
 * @package NeoFuture\Debug
 */
class Dumper
{
    /**
     * @param $value
     */

    public function outputInternalSessions($inline)
    {
        $sessions = Session::all();

        if (!$inline) {
            print("<h3>Internal Sessions <span class='code blue'>" . 'Session::getAll()</span></h3>');
        }


        //if (!isset($sessions[0])) {
        //    print("<div class='neofuture_dumper-noData'>No sessions to show</div>");
        //} else {
            if (is_array($sessions)) {
                print("<table border='1'>");
                print("<tr>");
                print("<th align='left'>Key</th>");
                print("<th align='left'>Value</th>");
                print("</tr>");
                foreach ($sessions as $key => $value) {
                    if($key!="infoBar"){
                        print("<tr>");
                        print("<td valign='top'>" . $key . "</td>");
                        print("<td valign='top'><pre>" . print_r($value, true) . "</pre></td>");
                        print("</tr>");
                    }
                }
                print("</table>");
            } else {
                print("<div class='neofuture_dumper-noData'>No sessions to show</div>");

            }
       // }
    }

    public function outputQueries($inline)
    {
        if (!$inline) {
            print("<h3>Queries</h3>");
        }

        if (Database::getQueryCount() > 0) {
            $sqlQueries = Database::getQueryLog();


            print("<table border='1'>");
            print("<tr>");
            print("<th align='center' width='10'>#</th>");
            print("<th align='left' width='20'>Duration</th>");
            print("<th align='left' width='20'>Database</th>");
            print("<th align='left'>Query</th>");

            print("</tr>");

            foreach ($sqlQueries as $key => $query) {
                print("<tr>");
                print("<td align='center'>" . ($key + 1) . "</td>");
                print("<td align='center'>" . formatDuration($query['duration']) . "</td>");
                print("<td align='center'>" . $query['database']. "</td>");
                print("<td align='left'>" . SqlFormatter::format($query['query'], $query['values']) . "</td>");
                print("</tr>");
            }

            print("</table>");
        } else {
            print("<div class='neofuture_dumper-noData'>No queries to show</div>");
        }
    }

    public function outputRoutes()
    {
        print("<h3>Routes</h3>");
        print("<table border='1'>");
        print("<tr>");
        print("<th align='center'>#</th>");
        print("<th align='center'>Verb</th>");
        print("<th align='left'>Route</th>");
        print("<th align='left'>Destination</th>");
        print("<th align='center'>Matched</th>");
        print("<th align='left'>Parameters</th>");
        print("<th align='center' width=30>POST</th>");
        print("<th align='center' width=30>GET</th>");
        print("</tr>");


        $matched = false;

        list($routes, $middleware) = Router::getRoutes();

        $words[] = "test";
        $words[] = "demo";
        $words[] = "slug";
        $words[] = "example";

        foreach ($routes as $key => $route) {
            $verb = $route['type'];
            $callback = $route['callback'];

            $pattern = preg_replace_callback("/\{(.*?)\}/",
                function ($matches) {
                    return "(.+?)";
                },
                $route['group'] . $route['pattern']
            );

            $pattern = '/^' . str_replace("/", "\/", $pattern) . '$/';

            if (preg_match($pattern, $_SERVER['REQUEST_URI'], $params) AND ($verb == $_SERVER['REQUEST_METHOD'] OR $verb == "ANY") AND $matched == false) {
                print("<tr class='bold'>");
                $matchedRoute = $route['pattern'];
            } else {
                print("<tr>");
            }
            print("<td align='center'>" . $key . "</td>");
            print("<td align='center'>" . $route['type'] . "</td>");

            print("<td >");

            print("<table border='1'>");

            if (isset($route['name'])) {
                print("<tr><td width='120px'><b>Name</b></td><td>" . ($route['name'] ?: "") . "</td></tr>");
            }

            if (isset($route['group'])) {
                print("<tr><td width='120px'><b>Group</b></td><td>" . ($route['group'] ?: "") . "</td></tr>");
            }

            print("<tr><td width='120px'><b>Route</b></td><td>" . $route['group'] . $route['pattern'] . "</td></tr>");
            print("<tr><td><b>Pattern</b></td><td>" . $pattern . "</td></tr>");
            print("<tr><td><b>Normalised</b></td><td>" . $this->normaliseRoute($route['group'] . $route['pattern']) . "</td></tr>");

            if (isset($middleware[$route['group']])) {
                print("<tr><td width='120px'><b>Group Middleware</b></td><td>" . (is_array($middleware[$route['group']]) ? join(", ", $middleware[$route['group']]) : $middleware[$route['group']]) . "</td></tr>");
            }

            if (isset($route['middleware'])) {
                print("<tr><td width='120px'><b>Route Middleware</b></td><td>" . (is_array($route['middleware']) ? join(", ", $route['middleware']) : $route['middleware']) . "</td></tr>");
            }


            print("</table>");

            print("</td>");

            print("<td valign='top'>");

            if (preg_match("/Closure/", print_r($callback, true))) {

                print("<pre class='blue' style='height:94px;overflow:auto;'><b>Closure</b>\n" . htmlentities($this->closureDump($callback)) . "</pre>");
            } else {
                print("<pre class='green' style='height:94px;overflow:auto;'>" . (preg_match("/::/", $callback) ? "<b>Class::Method</b>\n" : "<b>Function</b>\n") . $callback . "</pre>");
            }

            print("</td>");

            $originalPattern = str_replace("(.*?)", $words[(rand(0, count($words) - 1))], $route['group'] . $route['pattern']);

            $originalPattern = preg_replace_callback("/\{(.*?)\}/",
                function ($matches) use ($words) {
                    return $words[(rand(0, count($words) - 1))];
                },
                $originalPattern
            );

            $originalPattern = preg_replace_callback("/\(\[(.*?)\]\+\)/",
                function ($matches) {
                    return rand(0, 20);
                },
                $originalPattern
            );


            if (preg_match($pattern, $_SERVER['REQUEST_URI'], $params) AND ($route['type'] == $_SERVER['REQUEST_METHOD'] OR $route['type'] == "ANY") AND $matched == false) {
                $matched = true;
                array_shift($params);
                print("<td align='center'>");
                print("<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGrSURBVDjLvZPZLkNhFIV75zjvYm7VGFNCqoZUJ+roKUUpjRuqp61Wq0NKDMelGGqOxBSUIBKXWtWGZxAvobr8lWjChRgSF//dv9be+9trCwAI/vIE/26gXmviW5bqnb8yUK028qZjPfoPWEj4Ku5HBspgAz941IXZeze8N1bottSo8BTZviVWrEh546EO03EXpuJOdG63otJbjBKHkEp/Ml6yNYYzpuezWL4s5VMtT8acCMQcb5XL3eJE8VgBlR7BeMGW9Z4yT9y1CeyucuhdTGDxfftaBO7G4L+zg91UocxVmCiy51NpiP3n2treUPujL8xhOjYOzZYsQWANyRYlU4Y9Br6oHd5bDh0bCpSOixJiWx71YY09J5pM/WEbzFcDmHvwwBu2wnikg+lEj4mwBe5bC5h1OUqcwpdC60dxegRmR06TyjCF9G9z+qM2uCJmuMJmaNZaUrCSIi6X+jJIBBYtW5Cge7cd7sgoHDfDaAvKQGAlRZYc6ltJlMxX03UzlaRlBdQrzSCwksLRbOpHUSb7pcsnxCCwngvM2Rm/ugUCi84fycr4l2t8Bb6iqTxSCgNIAAAAAElFTkSuQmCC' title='Matched'>");
                print("</td>");
                print("<td>");
                print("<pre class='orange'>");
                var_export($params);
                print("</pre>");
                print("</td>");

                print("<td align='center'>");
                if ($route['type'] == "POST" OR $route['type'] == "ANY") {
                    print("<form method='post' action='" . $this->normaliseRoute($originalPattern) . "' target='_blank'>");
                    print("<input type='hidden' name='testInput' value='testValue'>");
                    print("<button>Go</button>");
                    print("</form>");
                }
                print("</td>");
                print("<td align='center'>");
                if ($route['type'] == "GET" OR $route['type'] == "ANY") {
                    print("<a href='" . $this->normaliseRoute($originalPattern) . "' target='_blank'>Go</a>");
                }
                print("</td>");

            } else {
                print("<td>");
                print("</td>");
                print("<td>");
                print("</td>");

                print("<td align='center'>");
                if ($route['type'] == "POST" OR $route['type'] == "ANY") {
                    print("<form method='post' action='" . $this->normaliseRoute($originalPattern) . "' target='_blank'>");
                    print("<input type='hidden' name='testInput' value='testValue'>");
                    print("<button>Go</button>");
                    print("</form>");
                }
                print("</td>");
                print("<td align='center'>");
                if ($route['type'] == "GET" OR $route['type'] == "ANY") {
                    print("<a href='" . $this->normaliseRoute($originalPattern) . "' target='_blank'>Go</a>");
                }
                print("</td>");
            }

            print("</td>");
            print("</tr>");
        }
        print("</table>");

    }

    public function outputVars()
    {
        if (isset($_GET)) {
            print("<h3>Get <span class='code blue'>" . '$' . '_GET</span></h3>');
            print("<pre class='blue'>");
            if (count($_GET) > 0) {
                var_export($_GET);
            } else {
                echo "Empty";
            }
            print("</pre>");

        }

        if (isset($_POST)) {
            print("<h3>Post <span class='code blue'>" . '$' . '_POST</span></h3>');
            print("<pre class='blue'>");
            if (count($_POST) > 0) {
                var_export($_POST);
            } else {
                echo "Empty";
            }
            print("</pre>");

        }

        if (isset($_FILES)) {
            print("<h3>Files <span class='code blue'>" . '$' . '_FILES</span></h3>');
            print("<pre class='blue'>");
            if (count($_FILES) > 0) {
                var_export($_FILES);
            } else {
                echo "Empty";
            }
            print("</pre>");

        }

        if (isset($_SESSION)) {
            print("<h3>Session <span class='code blue'>" . '$' . '_SESSION</span></h3>');
            print("<pre class='blue'>");
            if (count($_SESSION) > 0) {
                var_export($_SESSION);
            } else {
                echo "Empty";
            }
            print("</pre>");

        }

        if (isset($_COOKIE)) {
            print("<h3>Cookie <span class='code blue'>" . '$' . '_COOKIE</span></h3>');
            print("<pre class='blue'>");
            if (count($_COOKIE) > 0) {
                var_export($_COOKIE);
            } else {
                echo "Empty";
            }
            print("</pre>");

        }

        if (isset($_SERVER)) {
            print("<h3>Server <span class='code blue'>" . '$' . '_SERVER</span></h3>');
            print("<pre class='blue'>");
            if (count($_SERVER) > 0) {
                var_export($_SERVER);
            } else {
                echo "Empty";
            }
            print("</pre>");

        }
    }

    public function outputSettings()
    {
        if (isset($GLOBALS['setup'])) {
            print("<h3>Setup <span class='code blue'>" . '$' . 'GLOBALS[\'setup\']</span></h3>');
            print("<pre class='blue'>");
            var_export($GLOBALS['setup']);
            print("</pre>");
        }

        $files = Config::getAll();
        if (isset($files)) {
            foreach ($files as $fileName => $file) {
                print("<h3>Config File  - '" . $fileName . ".php' <span class='code blue'>" . 'Config::$' . 'files[\'' . $fileName . '\']</span></h3>');
                print("<pre class='green'>");
                var_export($files[$fileName]);
                print("</pre>");
            }
        }
    }

    public function outputDefined()
    {
        $definedFunctions = get_defined_functions();
        print("<h3>Defined User Functions</h3>");
        print("<table border='1'>");
        print("<tr>");
        print("<th align='left' width='33%'>Function</th>");
        print("<th align='left' width='33%'>Arguments</th>");
        print("<th align='left' width='33%'>Documents</th>");
        print("</tr>");
        foreach ($definedFunctions['user'] as $function) {
            print("<tr>");
            print("<td>" . $function . "</td>");
            $args = $this->getArguments($function);
            echo("<td>" . $args . "</td>");

            $rc = new \ReflectionFunction($function);
            if (var_export($rc->getDocComment(), true) != "false") {
                echo("<td><div class='code blue monospace'>" . nl2br(htmlentities($rc->getDocComment())) . "</div></td>");
            } else {
                echo("<td></td>");
            }

            echo("</tr>");
        }
        print("</table>");

        $declaredClasses = get_declared_classes();

        print("<h3>Defined Classes/Methods</h3>");
        print("<table border='1'>");
        print("<tr>");
        print("<th align='left' width='25%'>Class</th>");
        print("<th align='left' width='25%'>Method</th>");
        print("<th align='left' width='25%'>Arguments</th>");
        print("<th align='left' width='25%'>Documents</th>");
        print("</tr>");
        foreach ($declaredClasses as $className) {
            if (preg_match("/^NeoFuture/", $className)) {
                $classMethods = get_class_methods($className);

                foreach ($classMethods as $method) {
                    print("<tr>");
                    echo("<td>" . $className . "</td>");
                    echo("<td>" . $method . "</td>");
                    $args = $this->getArgumentsForClass($className, $method);
                    echo("<td>" . $args . "</td>");

                    $rc = new \ReflectionMethod($className, $method);
                    if (var_export($rc->getDocComment(), true) != "false") {
                        echo("<td><div class='code blue monospace'>" . nl2br(htmlentities($rc->getDocComment())) . "</div></td>");
                    } else {
                        echo("<td></td>");
                    }

                    print("</tr>");
                }
            }
        }
        print("</table>");

    }

    public function outputBacktrace()
    {
        $backtrace = $this->debugCallerData(3);

        if (isset($backtrace[0])) {
            print("<h3>Backtrace</h3>");

            foreach ($backtrace as $row) {
                print("<table border='1'>");
                print("<tr>");
                print("<th align='left' width='50%'>File</th>");
                print("<th align='left' width='50%'>Class</th>");
                print("</tr>");

                print("<tr>");
                print("<td>" . (isset($row['file']) ? str_replace(__DIR__, "", $row['file']) : '') . (isset($row['line']) ? " (Line: " . $row['line'] . ")" : '') . "</td>");
                print("<td>" . (isset($row['class']) ? $row['class'] : '') . (isset($row['type']) ? $row['type'] : '') . (isset($row['function']) ? $row['function'] : '') . "</td>");
                print("</tr>");
                if (isset($row['args'])) {
                    print("<tr>");
                    print("<th align='left' colspan='2'>Arguments</th>");
                    print("</tr>");
                    print("<tr>");
                    print("<td colspan='2'>" . (isset($row['args']) ? "<pre class='orange'>" . htmlentities(print_r($row['args'], true)) . "</pre>" : '') . "</td>");
                    print("</tr>");
                }
                print("</table>");

            }
        }

    }

    public function outputValues($value)
    {
        if (isset($value)) {
            foreach ($value as $key => $val) {
                print("<h3>Variable " . ($key + 1) . "</h3>");
                print("<pre class='red'>");
                print(var_export($val, true));
                print("</pre>");
            }
        }
    }

    public function output($value = null, $inline = false)
    {
        ob_start();
        ini_set('memory_limit', '2048M');

        if (isset($value)) {
            print("<title>System Information</title>");
        }

        print("<link rel='stylesheet' href='//" . Config::get("app.cdn") . "/delta/dumper.css'>");

        print("<div class='neofuture_dumper'>");

        if ($inline) {
            print("<div class='neofuture_dumper-tabs'>");
            print("<div id='neofuture_dumper-tab-1' class='neofuture_dumper-tab active'>Routes</div>");
            print("<div id='neofuture_dumper-tab-2' class='neofuture_dumper-tab'>Queries</div>");
            print("<div id='neofuture_dumper-tab-3' class='neofuture_dumper-tab'>Sessions</div>");
            print("<div id='neofuture_dumper-tab-4' class='neofuture_dumper-tab'>System Variables</div>");
            print("<div id='neofuture_dumper-tab-5' class='neofuture_dumper-tab'>System Settings &amp; Configuration</div>");
            print("<div id='neofuture_dumper-tab-6' class='neofuture_dumper-tab'>Defined Functions &amp; Class/Methods</div>");
            print("<div id='neofuture_dumper-tab-7' class='neofuture_dumper-tab'>Backtrace</div>");
            print("<div id='neofuture_dumper-tab-8' class='neofuture_dumper-tab'>Documents</div>");
            print("</div>");
        }

        print("<div class='neofuture_dumper-scroll'>");

        $this->outputValues($value);

        print("<div id='neofuture_dumper-content-tab-1' class='neofuture_dumper-tab-content'>");
        $this->outputRoutes();
        print("</div>");

        print("<div id='neofuture_dumper-content-tab-2' class='neofuture_dumper-tab-content'>");
        $this->outputQueries($inline);
        print("</div>");

        print("<div id='neofuture_dumper-content-tab-3' class='neofuture_dumper-tab-content'>");
        $this->outputInternalSessions($inline);
        print("</div>");

        print("<div id='neofuture_dumper-content-tab-4' class='neofuture_dumper-tab-content'>");
        $this->outputVars();
        print("</div>");

        print("<div id='neofuture_dumper-content-tab-5' class='neofuture_dumper-tab-content'>");
        $this->outputSettings();
        print("</div>");

        print("<div id='neofuture_dumper-content-tab-6' class='neofuture_dumper-tab-content'>");
        $this->outputDefined();
        print("</div>");

        print("<div id='neofuture_dumper-content-tab-7' class='neofuture_dumper-tab-content'>");
        $this->outputBacktrace();
        print("</div>");

        if ($inline) {
            print("<div id='neofuture_dumper-content-tab-8' class='neofuture_dumper-tab-content'>");
            print("<object data='//docs.neofuture.net/' style='width:100%;height:100%;overflow:auto;'>Loading...</object>");
            print("</div>");
        }

        print("</div>");

        print("</div>");
        if (isset($value)) {
            print($this->debugInfo(true));
        }
        return ob_get_clean();
    }


    /**
     * @param bool $inline
     * @return string
     */
    public function debugInfo($inline = false)
    {
        ob_start();
        $start = microtime(true);

        $m = memory_get_usage();
        $t = (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]);

        list($routes, $middleware) = Router::getRoutes();

        $matchedRoute = '';
        foreach ($routes as $key => $route) {
            $verb = $route['type'];

            $pattern = preg_replace_callback("/\{(.*?)\}/",
                function ($matches) {
                    return "(.+?)";
                },
                $route['group'] . $route['pattern']
            );

            $pattern = '/^' . str_replace("/", "\/", $pattern) . '$/';

            if (preg_match($pattern, $_SERVER['REQUEST_URI'], $params) AND ($verb == $_SERVER['REQUEST_METHOD'] OR $verb == "ANY")) {
                $matchedRoute = $route['group'] . $route['pattern'];
                break;
            }
        }

        print("<link rel='stylesheet' href='//" . Config::get("app.cdn") . "/delta/infoBar.css'>");
        print("<link rel=\"icon\" type=\"image/png\" href=\"/favicon/favicon.png\" />");

        print("<div class='neoFuture-infoBar' id='neoFuture-infoBar'>");
        print("<div id='neoFuture-infoBarContent'>");
        print("<div class='neoFuture-infoBarStats neoFuture-infoFull' id='neoFuture-infoBarStats'>");
        print("<b>Memory:</b> " . formatSize($m));
        print("<sep>&nbsp;</sep><b>Execution Time:</b> " . formatDuration($t));
        print("<sep>&nbsp;</sep><b>Debug Overhead:</b> [[DEBUG]]");

        $sqlQueries = Database::getQueryLog();

        $totalTime = 0;
        foreach ($sqlQueries as $query) {
            $totalTime += $query['duration'];
        }

        print("<sep>&nbsp;</sep><b>Queries:</b> " . Database::getQueryCount());
        print("<sep>&nbsp;</sep><b>Query Time:</b> " . formatDuration($totalTime));
        print("<sep>&nbsp;</sep><b>Route:</b> <span class='data'>" . $matchedRoute . "</span>");
        print("<sep>&nbsp;</sep><b>Method:</b> <span class='data'>" . $_SERVER['REQUEST_METHOD'] . "</span>");
        print("<sep class='request'>&nbsp;</sep><b>Request:</b> <span class='data request'>" . $_SERVER['REQUEST_URI'] . "</span>");
        print("</div>");

        //print("<sep>&nbsp;</sep>");

        print("<div class='neoFuture-infoBarIcons neoFuture-infoFull' id='neoFuture-infoBarIcons'>");
        if (!$inline) {
            print("<span id='neoFutureMaxInfoBar' class='neoFuture-maxInfoBar'></span>");
            print("<span id='neoFutureCloseInfoBar' class='neoFuture-closeInfoBar'></span>");
        }
        print("</div>");

        print("</div>");


        print("</div>");

        if(Session::has("infoBar")){
            $session = Session::get("infoBar");
        } else {
            $session = [];
        }

        $id = count($session);

        $session[$id]['memory'] = formatSize($m);
        $session[$id]['executionTime'] = $t . " ms";
        $session[$id]['queries'] = Database::getQueryCount();
        $session[$id]['queryTime'] = formatDuration($totalTime);
        $session[$id]['route'] = $matchedRoute;
        $session[$id]['requestMethod'] = $_SERVER['REQUEST_METHOD'];
        $session[$id]['requestUri'] = $_SERVER['REQUEST_URI'];


        Session::set("infoBar", $session);

        if (!$inline) {
            print("<div id='neoFuture-infoPane' class='neoFuture-infoPane'>");
            print($this->output(null, true));
            print("</div>");
        }

        if (!$inline) {
            print("<script async src='//" . setup("CDN") . "/delta/infoBar-min.js'></script>");
        }

        $diff = microtime(true) - $start;

        $out = ob_get_clean();

        $out = str_replace("[[DEBUG]]", formatDuration($diff), $out);

        return $out;
    }

    /**
     * @param $pattern
     * @return mixed
     */
    private function normaliseRoute($pattern)
    {
        $pattern = str_replace("/^", "", $pattern);
        $pattern = str_replace("$/", "", $pattern);
        $pattern = str_replace("\/", "/", $pattern);

        $pattern = str_replace("(.*?)", "*", $pattern);
        $pattern = str_replace("(", "", $pattern);
        $pattern = str_replace(")", "", $pattern);
        return $pattern;
    }

    /**
     * @param $trace
     * @return array
     */
    private function debugCallerData($trace)
    {
        $backtrace = debug_backtrace();
        for ($i = 0; $i < $trace; $i++) {
            array_shift($backtrace);
        }
        return $backtrace;
    }

    /**
     * @param $closure
     * @return string
     */
    private function closureDump($closure)
    {
        $closureFunction = 'function (';
        $reflectionFunction = new \ReflectionFunction($closure);

        $closureFunction .= $this->getArguments($closure);

        $closureFunction .= '){' . PHP_EOL;

        $lines = file($reflectionFunction->getFileName());
        for ($line = $reflectionFunction->getStartLine(); $line < $reflectionFunction->getEndLine(); $line++) {
            $closureFunction .= $lines[$line];
        }

        //clean up the end of the closure function before returning
        $closureFunction = explode("->where(", $closureFunction);
        $closureFunction = preg_replace("/\)$/", ");", trim($closureFunction[0]));

        return $closureFunction;
    }

    /**
     * @param $closure
     * @param null $method
     * @return string
     */
    private function getArguments($closure, $method = null)
    {
        $reflectionFunction = new \ReflectionFunction($closure);
        return $this->processParams($reflectionFunction);

    }

    /**
     * @param $class
     * @param $method
     * @return string
     */
    private function getArgumentsForClass($class, $method)
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);
        return $this->processParams($reflectionMethod);
    }

    /**
     * @param $reflection
     * @return string
     */
    private function processParams($reflectionData)
    {
        $params = array();

        foreach ($reflectionData->getParameters() as $parameter) {
            $paramString = '';

            if ($parameter->isArray()) {
                $paramString .= 'array ';
            } else if ($parameter->getClass()) {
                $paramString .= $parameter->getClass()->name . ' ';
            }

            if ($parameter->isPassedByReference()) {
                $paramString .= '&';
            }

            $paramString .= ($parameter->isVariadic() ? "..." : "") . '$' . $parameter->name;

            if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
                $paramString .= ' = ' . var_export($parameter->getDefaultValue(), TRUE);
            }

            $params [] = $paramString;
        }

        return implode(', ', $params);
    }

}