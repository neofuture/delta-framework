<?php

namespace NeoFuture\Library;

use NeoFuture\Debug\Dumper;

use Whoops\Run;
use Whoops\Handler;
use NeoFuture\Library\Database;
use NeoFuture\Library\Config;
use NeoFuture\Library\Support\Filesystem;

class BaseKernel
{

    public $html;

    public function __construct()
    {
        //require_once __DIR__ . '/../Library/Support/Helpers.php';

        if (setup("DEBUG") == true) {
            $whoops = new Run;
            $whoops->pushHandler(new Handler\PrettyPageHandler);
            $whoops->register();
        }

        Session::start();
        Filesystem::cleanFileSystem();

        if(Config::get("cache.driver") == "database") {
            Database::raw("DELETE FROM cache WHERE UNIX_TIMESTAMP(NOW()) > (registered + ttl) AND ttl > 0");
        }

        if(Config::get("session.driver") == "database"){
            Database::raw("DELETE FROM sessions WHERE UNIX_TIMESTAMP(NOW()) > (registered + ttl) AND ttl > 0");
        }
    }

    public function handle($request)
    {

        $html = Router::execute($request);

        if (setup("DEBUG") == true && setup("INFO_BAR") == true) {
            $infoBar = (new Dumper)->debugInfo();
            if (preg_match("/<\/body/", $html)) {
                $html = preg_replace("/\<body(.*?)\>/", "<body" . '$1' . "><div id='neofuture-dumper-page'>", $html);

                $html = str_replace("</body", $infoBar . "</div></body", $html);
            } else {
                if (!json_decode($html)) {
                    $html .= $infoBar;
                }
            }
        }

        $this->html = $html;

        return $this;
    }

    public function send()
    {

        $response = new Response($this->html);

        echo $response;
    }
}