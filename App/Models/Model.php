<?php

namespace App\Models;

use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;

class Model {
    public static array $searchableFields = ["user_id", "name", "description", "notes", "author", "licence",
        "imported_name", "imported_description", "imported_author", "imported_licence", "import_source"];

    public static function createModel(
        // Base information
        int    $userId,
        string $name,
        array  $links,
        string $description,
        string $notes,
        bool   $favorite,
        string $author,
        string $licence,
        // Imported models
        string $imported_name = null,
        string $imported_description = null,
        string $imported_author = null,
        string $imported_licence = null,
        string $import_source = null
    ): int {
        $createQuery = DB::connection()
            ->insert([
                "user_id",
                "name",
                "links",
                "description",
                "notes",
                "favorite",
                "author",
                "licence",
                "imported_name",
                "imported_description",
                "imported_author",
                "imported_licence",
                "import_source"
            ])
            ->into("models")
            ->values(
                $userId,
                $name,
                json_encode($links, true),
                $description,
                $notes,
                DB::boolValue($favorite),
                $author,
                $licence,
                $imported_name,
                $imported_description,
                $imported_author,
                $imported_licence,
                $import_source
            );

        if ($createQuery->execute()) {
            return DB::connection()->lastInsertId();
        }

        return -1;
    }

    public static function getModel(int $userId, int $modelId) {
        return DB::connection()
            ->select()
            ->from("models")
            ->where(
                new Grouping(
                    "AND",
                    new Conditional("id", "=", $modelId),
                    new Conditional("user_id", "=", $userId)
                )
            )
            ->execute()
            ->fetch();
    }

    public static function deleteModel(int $userId, int $modelId): bool|\PDOStatement {
        ModelFile::deleteModelFiles($userId, $modelId);
        ModelTag::deleteModelTags($userId, $modelId);

        return DB::connection()
            ->delete()
            ->from("models")
            ->where(new Grouping(
                "AND",
                new Conditional("id", "=", $modelId),
                new Conditional("user_id", "=", $userId)
            ))
            ->execute();
    }

    public static function searchModels(int $userId, string $searchTerm, array $searchFields): bool|array {
        $searchFields = array_intersect($searchFields, Model::$searchableFields);

        $searches = array();
        foreach ($searchFields as $f) {
            $searches[] = new Conditional($f, "LIKE", "%$searchTerm%");
        }

        return DB::connection()
            ->select()
            ->from("models")
            ->where(
                new Grouping(
                    "AND",
                    new Conditional("user_id", "=", $userId),
                    new Grouping(
                        "OR",
                        ...$searches
                    )
                )
            )
            ->execute()
            ->fetchAll();
    }
}
