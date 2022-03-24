<?php

namespace App\Models;

use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use PDOStatement;

class ModelFile {
    public static function getFiles(int $userId, int $modelId): array {
        return DB::connection()->select()
            ->from("model_files")
            ->where(new Grouping(
                "AND",
                new Conditional("user_id", "=", $userId),
                new Conditional("model_id", "=", $modelId)
            ))
            ->execute()
            ->fetchAll();
    }

    public static function getFile(int $userId, int $fileId) {
        return DB::connection()->select()
            ->from("model_files")
            ->where(new Grouping(
                "AND",
                new Conditional("id", "=", $fileId),
                new Conditional("user_id", "=", $userId)
            ))
            ->execute()
            ->fetch();
    }
}
