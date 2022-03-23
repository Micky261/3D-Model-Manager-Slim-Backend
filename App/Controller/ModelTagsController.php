<?php

namespace App\Controller;

use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelTagsController {
    public function getAllTags(Request $request, Response $response): Response {
        $userId = $request->getAttribute("sessionUserId");

        $tags = DB::connection()->select(['tag', "count" => 'COUNT(tag)'])
            ->from("model_tags")
            ->where(new Conditional("user_id", "=", $userId))
            ->groupBy('tag')
            ->orderBy('count')
            ->execute()
            ->fetchAll();

        $response->getBody()->write(json_encode($tags));
        return $response;
    }


    public function getTags(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $tags = DB::connection()->select()
            ->from("model_tags")
            ->where(new Grouping(
                "AND",
                new Conditional("model_id", "=", $modelId),
                new Conditional("user_id", "=", $userId)
            ))
            ->execute()
            ->fetchAll();

        $response->getBody()->write(json_encode($tags));
        return $response;
    }


    public function setTag(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $tag = $args["tag"];

        $tagRow = $this->selectTag($userId, $modelId, $tag);

        if ($tagRow === false) { // Tag not associated with model yet
            DB::connection()
                ->insert(["user_id", "model_id", "tag"])
                ->into("model_tags")
                ->values($userId, $modelId, $tag)
                ->execute();

            $tagRow = $this->selectTag($userId, $modelId, $tag);

            $response->getBody()->write(json_encode($tagRow));
            return $response->withStatus(200);
        }

        return $response->withStatus(200);
    }

    private function selectTag(int $userId, int $modelId, string $tag) {
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

    public function removeTag(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $tag = $args["tag"];

        DB::connection()
            ->delete()
            ->from("model_tags")
            ->where(new Grouping(
                "AND",
                new Conditional("user_id", "=", $userId),
                new Conditional("model_id", "=", $modelId),
                new Conditional("tag", "=", $tag)
            ))
            ->execute();

        return $response->withStatus(200);
    }
}
