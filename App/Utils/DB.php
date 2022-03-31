<?php

namespace App\Utils;

use FaaPz\PDO\Clause\Raw;
use FaaPz\PDO\Database;

/**
 * Open Database connection
 */
class DB {
    private static bool $loaded = false;
    private static DB $instance;
    private Database $database;

    private function __construct() {
        $dbConfig = Configuration::database();
        $dsn = $dbConfig["db"] .
            ":host=" . $dbConfig["host"] .
            ";dbname=" . $dbConfig["name"] .
            ";charset=utf8";
        $usr = $dbConfig["username"];
        $pwd = $dbConfig["password"];

        $this->database = new Database($dsn, $usr, $pwd);
    }

    public static function connection(): Database {
        return self::getInstance()->database;
    }

    public static function getInstance(): DB {
        if (!self::$loaded) {
            self::$loaded = true;
            self::$instance = new DB();
        }

        return self::$instance;
    }

    public static function generateSessionId(int $length = 64): string {
        $length = ($length < 4) ? 4 : $length;
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }

    public static function boolValue(mixed $val): mixed {
        if (Configuration::database()["db"] == "mysql") {
            $val = is_bool($val) ? ($val ? 'true' : 'false') : $val;
            $val = is_numeric($val) ? ($val == 1 ? 'true' : 'false') : $val;
            return new Raw($val);
        }

        return $val;
    }
}

