<?php

namespace App\Models;

use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;

class ModelFile {
    public static function getFileBasePath(int $userId, int $modelId): string {
        return "../upload/$userId/$modelId/";
    }

    public static function getFileTypePath(int $userId, int $modelId, string $type): string {
        return self::getFileBasePath($userId, $modelId) . "$type/";
    }

    public static function getFilePath(int $userId, int $modelId, string $type, string $filename): string {
        return self::getFileTypePath($userId, $modelId, $type) . "$filename";
    }

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

    public static function createFileDBEntry(
        int $userId, int $modelId, string $type, string $filename, int $filesize, int $position = 999
    ): int {
        $createQuery = DB::connection()
            ->insert(["user_id", "model_id", "type", "filename", "position", "size"])
            ->into("model_files")
            ->values($userId, $modelId, $type, $filename, $position, $filesize);

        if ($createQuery->execute()) {
            return DB::connection()->lastInsertId();
        }

        return -1;
    }

    public static function moveFileOnDisk(string $source, string $path, string $filename): int {
        if (!is_dir($path)) mkdir($path, 0755, true);

        return file_put_contents($path . $filename, fopen($source, "r"));
    }
}
