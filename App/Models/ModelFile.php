<?php

namespace App\Models;

use App\Storage\Storage;
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

    public static function getFilesByType(int $userId, int $modelId, string $type): array {
        return DB::connection()->select()
            ->from("model_files")
            ->where(new Grouping(
                "AND",
                new Conditional("user_id", "=", $userId),
                new Conditional("model_id", "=", $modelId),
                new Conditional("type", "=", $type)
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

    public static function deleteFile(int $userId, int $fileId): bool|PDOStatement {
        return DB::connection()->delete()
            ->from("model_files")
            ->where(new Grouping(
                "AND",
                new Conditional("id", "=", $fileId),
                new Conditional("user_id", "=", $userId)
            ))
            ->execute();
    }

    public static function fileExists(int $userId, int $modelId, string $type, string $filename): bool {
        return DB::connection()->select(["COUNT(id) as c"])
                ->from("model_files")
                ->where(new Grouping(
                    "AND",
                    new Conditional("user_id", "=", $userId),
                    new Conditional("model_id", "=", $modelId),
                    new Conditional("type", "=", $type),
                    new Conditional("filename", "=", $filename),
                ))
                ->execute()
                ->fetch()["c"] != 0;
    }

    public static function getStorageSize(string $storageName): int {
        return DB::connection()->select(["COALESCE(SUM(size),0) as size_acc"])
            ->from("model_files")
            ->where(
                new Conditional("storage", "=", $storageName)
            )
            ->execute()
            ->fetch()["size_acc"];
    }

    public static function createFileDBEntry(
        int $userId, int $modelId, string $storageName, string $type, string $filename, int $filesize, int $position = 999
    ): int {
        $createQuery = DB::connection()
            ->insert(["user_id", "model_id", "storage", "type", "filename", "position", "size"])
            ->into("model_files")
            ->values($userId, $modelId, $storageName, $type, $filename, $position, $filesize);

        if ($createQuery->execute()) {
            return DB::connection()->lastInsertId();
        }

        return -1;
    }

    public static function moveFileOnDisk(string $source, string $path, string $filename): int {
        if (!is_dir($path)) mkdir($path, 0755, true);

        return file_put_contents($path . $filename, fopen($source, "r"));
    }

    public static function deleteModelFiles(int $userId, int $modelId): void {
        $files = self::getFiles($userId, $modelId);

        foreach ($files as $file) {
            $storage = Storage::getStorageClassByName($file["storage"]);
            $storage->deleteFile($storage->getFilePath($userId, $modelId, $file["type"], $file["filename"]));
            self::deleteFile($userId, $file["id"]);
        }
    }
}
