<?php

namespace App\Utils;

/**
 * Load config from file system
 */
class Configuration {
    private static bool $loaded = false;
    private static Configuration $instance;
    private array $config;

    private function __construct() {
        $this->config = json_decode(file_get_contents('../app-config.json'), true);
    }

    public static function general() {
        return self::getInstance()->config["general"];
    }

    public static function getInstance(): Configuration {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$instance = new Configuration();
        }

        return self::$instance;
    }

    public static function database() {
        return self::getInstance()->config["database"];
    }

    public static function importer() {
        return self::getInstance()->config["importer"];
    }

    public static function mail() {
        return self::getInstance()->config["mail"];
    }

    public static function storage() {
        return self::getInstance()->config["storage"];
    }
}
