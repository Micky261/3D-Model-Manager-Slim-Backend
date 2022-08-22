<?php

namespace App\Models;

use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;

class ModelTag {
    public static function getTags(int $userId, int $modelId = -1): bool|array {
        if ($modelId === -1) {
            $where = new Conditional("user_id", "=", $userId);
        } else {
            $where = new Grouping(
                "AND",
                new Conditional("model_id", "=", $modelId),
                new Conditional("user_id", "=", $userId)
            );
        }

        return DB::connection()->select(['tag', "count" => 'COUNT(tag)'])
            ->from("model_tags")
            ->where($where)
            ->groupBy('tag')
            ->orderBy('count')
            ->execute()
            ->fetchAll();
    }

    public static function getTag(int $userId, int $modelId, string $tag) {
        return DB::connection()
            ->select()
            ->from("model_tags")
            ->where(
                new Grouping(
                    "AND",
                    new Conditional("user_id", "=", $userId),
                    new Conditional("model_id", "=", $modelId),
                    new Conditional("tag", "=", $tag)
                )
            )
            ->execute()
            ->fetch();
    }

    public static function createTag(int $userId, int $modelId, string $tag): int {
        $createQuery = DB::connection()
            ->insert(["user_id", "model_id", "tag"])
            ->into("model_tags")
            ->values($userId, $modelId, $tag);

        if ($createQuery->execute()) {
            return DB::connection()->lastInsertId();
        }

        return -1;
    }

    public static function deleteModelTags(int $userId, int $modelId): bool|\PDOStatement {
        return DB::connection()
        ->delete()
        ->from("model_tags")
        ->where(new Grouping(
            "AND",
            new Conditional("model_id", "=", $modelId),
            new Conditional("user_id", "=", $userId)
        ))
        ->execute();
    }
}
