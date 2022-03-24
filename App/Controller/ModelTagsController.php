<?php

namespace App\Controller;

use App\Models\ModelTag;
use App\Utils\DB;
use FaaPz\PDO\Clause\Conditional;
use FaaPz\PDO\Clause\Grouping;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ModelTagsController {
    public function getAllTags(Request $request, Response $response): Response {
        $userId = $request->getAttribute("sessionUserId");

        $response->getBody()->write(json_encode(ModelTag::getTags($userId)));
        return $response;
    }


    public function getTags(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];

        $response->getBody()->write(json_encode(ModelTag::getTags($userId, $modelId)));
        return $response;
    }


    public function setTag(Request $request, Response $response, $args): Response {
        $userId = $request->getAttribute("sessionUserId");
        $modelId = $args["id"];
        $tag = $args["tag"];

        $tagRow = ModelTag::getTag($userId, $modelId, $tag);

        if ($tagRow === false) { // Tag not associated with model yet
            ModelTag::createTag($userId, $modelId, $tag);
            $tagRow = ModelTag::getTag($userId, $modelId, $tag);

            $response->getBody()->write(json_encode($tagRow));
            return $response->withStatus(200);
        }

        return $response->withStatus(200);
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
