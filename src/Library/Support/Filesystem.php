<?php

namespace NeoFuture\Library\Support;

use NeoFuture\Library\Cipher;
use NeoFuture\Library\Application;

class Filesystem
{

    public static function save($path, $file, $content, $ttl = 0)
    {
        $basePath = self::getPath();
        $fileName = ($path . "-" . $file);
        $fileContent = Cipher::encrypt($content);

        file_put_contents($basePath . "/" . $fileName, $fileContent);
        file_put_contents($basePath . "/.index", "\n" . $ttl . "=" . $fileName, FILE_APPEND);

        self::cleanFileSystem();
    }

    public static function open($path, $file)
    {
        self::cleanFileSystem();

        $basePath = self::getPath();
        $fileName = ($path . "-" . $file);

        if (!is_file($basePath . "/" . $fileName)) {
            return false;
        }

        $fileContent = file_get_contents($basePath . "/" . $fileName, true);

        $content = Cipher::decrypt($fileContent);

        return $content;
    }

    public static function delete($path, $file)
    {
        $basePath = self::getPath();
        $fileName = ($path . "-" . $file);

        if (!is_file($basePath . "/" . $fileName)) {
            return false;
        }

        unlink($basePath . "/" . $fileName);
        self::cleanFileSystem();
        return false;
    }

    public static function cleanFileSystem()
    {
        $basePath = self::getPath();

        $newIndex = [];
        if (is_file($basePath . "/.index")) {
            $index = file_get_contents($basePath . "/.index");
            foreach (explode("\n", $index) as $file) {
                if (preg_match("/=/", $file)) {
                    list($ttl, $name) = explode("=", $file);
                    if (is_file($basePath . "/" . $name)) {
                        if ((time() - filemtime($basePath . "/" . $name) > $ttl) AND $ttl > 0) {
                            unlink($basePath . "/" . $name);
                        } else {
                            $newIndex[$basePath . "/" . $name] = $file;
                        }
                    }

                }
            }
            file_put_contents($basePath . "/.index", trim(implode("\n", $newIndex)));
        }


    }

    public static function getPath()
    {
        return realpath(Application::getPath() . "/" . setup("FILESTORE"));
    }
}